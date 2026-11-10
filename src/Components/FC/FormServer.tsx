import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import request from '@hubleto/react-ui/core/Request';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import { useRecordField } from '@hubleto/react-ui/components/fc/FormRecordStore';
import InputTags from '@hubleto/react-ui/components/fc/Inputs/Tags';
import CalendarTab from '@hubleto/apps/Calendar/Components/FC/CalendarTab';
import FormServerActivity from './FormServerActivity';
import ServerOverview from './ServerOverview';
import TableStackItems from './TableStackItems';
import TableHealthStatuses from './TableHealthStatuses';
import TableActivityReports from './TableActivityReports';

export interface FormServerProps extends FormProps {}

const componentName = 'FormServer'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** TabDefault: action bar, overview dashboard and all fields of the server */
const TabDefault = (props: FormServerProps) => {
  const form = React.useContext(FormMetaContext);
  const pdf: string = useRecordField('pdf', '');
  const [generating, setGenerating] = React.useState<boolean>(false);

  const generateReport = () => {
    setGenerating(true);
    request.get('server-report/api/generate-report', {idServer: form.id}, (data: any) => {
      setGenerating(false);
      if (data.status == 'success') form.reload();
      else alert(data.message ?? T.translate('The report could not be generated.'));
    });
  };

  return <>
    {form.id > 0 ? <div className='flex gap-2 flex-wrap mb-2'>
      <button className='btn btn-small btn-transparent' disabled={generating} onClick={generateReport}>
        <span className='icon'><i className='fas fa-file-pdf'></i></span>
        <span className='text'>{generating ? T.translate('Generating...') : T.translate('Generate server report (PDF)')}</span>
      </button>
      {pdf ? <a
        className='btn btn-small btn-transparent'
        target='_blank'
        href={globalThis.hubleto.config.uploadUrl + '/' + pdf}
      >
        <span className='icon'><i className='fas fa-download'></i></span>
        <span className='text'>{T.translate('Open the latest report')}</span>
      </a> : null}
    </div> : null}

    {form.id > 0
      ? <ServerOverview idServer={form.id} />
      : <div className='alert alert-info mb-2'>{T.translate('The overview of the server is shown after the server is saved.')}</div>
    }

    <div className='flex-dyn gap-2 mt-2'>
      <div className='flex-1'>
        <Input field='name' />
        <Input field='description' />
        <Input field='connection_details' />
        <Input field='doc_url_1' />
        <Input field='doc_url_2' />
        <Input field='doc_url_3' />
        <Input field='id_customer' />
        <Input field='id_workflow_step' />
        <Input field='note' />
      </div>
      <div className='flex-1'>
        <h4 className='font-bold mb-1'>{T.translate('Location')}</h4>
        <Input field='id_datacenter' />
        <Input field='room' />
        <Input field='rack' />
        <Input field='rack_position' />
        <h4 className='font-bold mt-2 mb-1'>{T.translate('Hardware')}</h4>
        <Input field='id_provider' />
        <h4 className='font-bold mt-2 mb-1'>{T.translate('Sharing')}</h4>
        <Input field='shared_with' />
      </div>
    </div>
  </>;
}

/** TabStack */
const TabStack = (props: FormServerProps) => {
  const form = React.useContext(FormMetaContext);
  if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Create the server first')}</div>;
  return <TableStackItems
    tag={'table_server_stack_item'}
    parentForm={form}
    uid={props.uid + '_table_server_stack_item'}
    idServer={form.id}
  />;
}

/** TabHealth */
const TabHealth = (props: FormServerProps) => {
  const form = React.useContext(FormMetaContext);
  if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Create the server first')}</div>;
  return <TableHealthStatuses
    tag={'table_server_health_status'}
    parentForm={form}
    uid={props.uid + '_table_server_health_status'}
    idServer={form.id}
  />;
}

/** TabCalendar: the same principle as in FormDeal */
const TabCalendar = (props: FormServerProps) => {
  const form = React.useContext(FormMetaContext);
  return <CalendarTab
    loadEventsEndpoint={'calendar/api/get-calendar-events?calendar=server-report&idServer=' + form.id}
    logActivityEndpoint={'server-report/api/log-activity?idServer=' + form.id}
    renderActivityForm={(calendarTab: any) => {
      return <FormServerActivity idServer={form.id} calendarTab={calendarTab}></FormServerActivity>;
    }}
  ></CalendarTab>;
}

/** TabReports */
const TabReports = (props: FormServerProps) => {
  const form = React.useContext(FormMetaContext);
  if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Create the server first')}</div>;
  return <TableActivityReports
    tag={'table_server_activity_report'}
    parentForm={form}
    uid={props.uid + '_table_server_activity_report'}
    idServer={form.id}
  />;
}

/** FormServer */
const FormServer = (props: FormServerProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Server'}
    urlSlug='server-report'
    endpointParams={{saveRelations: ['TAGS']}}
    renderTopInputs={(form: FormMeta) => {
      const TAGS: Array<any> = useRecordField('TAGS', []);
      return <div className='modal-top-inputs'>
        <Input field='is_closed' renderOnlyInputField />
        <Input>
          <InputTags
            field='TAGS'
            value={TAGS}
            model={parentApp + '/Models/Tag'}
            targetColumn='id_server'
            sourceColumn='id_tag'
            colorColumn='_LOOKUP_COLOR'
            showSelect={false}
            showTagButtons={true}
            editTagsUrl='server-report/tags'
            onChange={(input: any, value: any) => {
              form.changeField(input, value);
            }}
            onNewTag={(title: string) => {
              return { id: -1, name: title, color: '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0') }
            }}
          ></InputTags>
        </Input>
        <Input field='id_owner' renderOnlyInputField />
        <Input field='id_manager' renderOnlyInputField />
      </div>
    }}
    title={{fields: ['name'], sub: T.translate('Server')}}
    tabs={{
      default: {title: <b>{T.translate('Server')}</b>, content: () => <TabDefault {...props} />},
      stack: {title: T.translate('Application stack'), content: () => <TabStack {...props} />},
      health: {title: T.translate('Health history'), content: () => <TabHealth {...props} />},
      calendar: {title: T.translate('Calendar'), content: () => <TabCalendar {...props} />},
      reports: {title: T.translate('Reports'), content: () => <TabReports {...props} />},
    }}
    {...props}
  ></Form>;
}

export default FormServer;
