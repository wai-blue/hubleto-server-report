import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';

export interface FormHealthStatusProps extends FormProps {}

const componentName = 'FormHealthStatus'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** TabDefault */
const TabDefault = (props: FormHealthStatusProps) => {
  return <>
    <div className='flex-dyn gap-2'>
      <div className='flex-1'>
        <Input field='id_server' />
        <Input field='datetime_checked' />
        <Input field='overall' />
        <Input field='id_checked_by' />
        <Input field='note' />
      </div>
      <div className='flex-1'>
        <Input field='status' />
      </div>
    </div>
  </>;
}

/** FormHealthStatus */
const FormHealthStatus = (props: FormHealthStatusProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/HealthStatus'}
    urlSlug='server-report/health'
    title={{fields: ['datetime_checked'], sub: T.translate('Health check')}}
    tabs={{
      default: {title: <b>{T.translate('Health check')}</b>, content: () => <TabDefault {...props} />},
    }}
    {...props}
  ></Form>;
}

export default FormHealthStatus;
