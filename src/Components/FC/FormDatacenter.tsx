import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import TableServers from './TableServers';

export interface FormDatacenterProps extends FormProps {}

const componentName = 'FormDatacenter'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** TabDefault */
const TabDefault = (props: FormDatacenterProps) => {
  return <div className='flex-dyn gap-2'>
    <div className='flex-1'>
      <h4 className='font-bold mb-1'>{T.translate('General')}</h4>
      <Input field='name' />
      <Input field='description' />
      <h4 className='font-bold mt-2 mb-1'>{T.translate('Address')}</h4>
      <Input field='street_line_1' />
      <Input field='street_line_2' />
      <Input field='city' />
      <Input field='postal_code' />
      <Input field='id_country' />
    </div>
    <div className='flex-1'>
      <h4 className='font-bold mb-1'>{T.translate('Access information')}</h4>
      <Input field='access_info' />
      <h4 className='font-bold mt-2 mb-1'>{T.translate('Notes')}</h4>
      <Input field='note' />
    </div>
  </div>;
}

/** TabServers */
const TabServers = (props: FormDatacenterProps) => {
  const form = React.useContext(FormMetaContext);
  if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Create the datacenter first')}</div>;
  return <TableServers
    tag={'table_datacenter_server'}
    parentForm={form}
    uid={props.uid + '_table_datacenter_server'}
    idDatacenter={form.id}
  />;
}

/** FormDatacenter */
const FormDatacenter = (props: FormDatacenterProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Datacenter'}
    urlSlug='server-report/datacenters'
    title={{fields: ['name'], sub: T.translate('Datacenter')}}
    tabs={{
      default: {title: <b>{T.translate('Datacenter')}</b>, content: () => <TabDefault {...props} />},
      servers: {title: T.translate('Servers'), content: () => <TabServers {...props} />},
    }}
    {...props}
  ></Form>;
}

export default FormDatacenter;
