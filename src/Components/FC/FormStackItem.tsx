import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';

export interface FormStackItemProps extends FormProps {}

const componentName = 'FormStackItem'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** TabDefault */
const TabDefault = (props: FormStackItemProps) => {
  return <>
    <div className='alert alert-info mb-2'>
      {T.translate('Do not store passwords or other secrets in the configuration. Add a new record to keep the history of versions.')}
    </div>
    <div className='flex-dyn gap-2'>
      <div className='flex-1'>
        <Input field='id_server' />
        <Input field='name' />
        <Input field='version' />
        <Input field='is_current' />
        <Input field='note' />
      </div>
      <div className='flex-1'>
        <Input field='configuration' />
        <Input field='datetime_recorded' />
        <Input field='id_recorded_by' />
      </div>
    </div>
  </>;
}

/** FormStackItem */
const FormStackItem = (props: FormStackItemProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/StackItem'}
    urlSlug='server-report/stack'
    title={{fields: ['name', 'version'], sub: T.translate('Application')}}
    tabs={{
      default: {title: <b>{T.translate('Application')}</b>, content: () => <TabDefault {...props} />},
    }}
    {...props}
  ></Form>;
}

export default FormStackItem;
