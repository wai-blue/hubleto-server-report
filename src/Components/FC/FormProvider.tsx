import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormMeta, type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form, { FormMetaContext } from '@hubleto/react-ui/components/fc/Form';
import Input from '@hubleto/react-ui/components/fc/FormComponents/Input';
import TableServers from './TableServers';

export interface FormProviderProps extends FormProps {}

const componentName = 'FormProvider'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** TabDefault */
const TabDefault = (props: FormProviderProps) => {
  return <div className='flex-dyn gap-2'>
    <div className='flex-1'>
      <h4 className='font-bold mb-1'>{T.translate('General and contact')}</h4>
      <Input field='name' />
      <Input field='contact_person' />
      <Input field='email' />
      <Input field='phone' />
      <Input field='website' />
      <h4 className='font-bold mt-2 mb-1'>{T.translate('Address')}</h4>
      <Input field='street_line_1' />
      <Input field='street_line_2' />
      <Input field='city' />
      <Input field='postal_code' />
      <Input field='id_country' />
    </div>
    <div className='flex-1'>
      <h4 className='font-bold mb-1'>{T.translate('Invoicing details')}</h4>
      <Input field='invoicing_name' />
      <Input field='invoicing_address' />
      <Input field='tax_id' />
      <Input field='vat_id' />
      <Input field='invoicing_email' />
      <h4 className='font-bold mt-2 mb-1'>{T.translate('Notes')}</h4>
      <Input field='note' />
    </div>
  </div>;
}

/** TabServers */
const TabServers = (props: FormProviderProps) => {
  const form = React.useContext(FormMetaContext);
  if (form.id <= 0) return <div className='alert alert-info'>{T.translate('Create the provider first')}</div>;
  return <TableServers
    tag={'table_provider_server'}
    parentForm={form}
    uid={props.uid + '_table_provider_server'}
    idProvider={form.id}
  />;
}

/** FormProvider */
const FormProvider = (props: FormProviderProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Provider'}
    urlSlug='server-report/providers'
    title={{fields: ['name'], sub: T.translate('Hardware provider')}}
    tabs={{
      default: {title: <b>{T.translate('Provider')}</b>, content: () => <TabDefault {...props} />},
      servers: {title: T.translate('Servers'), content: () => <TabServers {...props} />},
    }}
    {...props}
  ></Form>;
}

export default FormProvider;
