import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form from '@hubleto/react-ui/components/fc/Form';

export interface FormServerHasTagProps extends FormProps {}

const componentName = 'FormServerHasTag'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** FormServerHasTag */
const FormServerHasTag = (props: FormServerHasTagProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ServerHasTag'}
    urlSlug='server-report/server-has-tags'
    title={{field: 'id', sub: T.translate('Server tag assignment')}}
    {...props}
  ></Form>;
}

export default FormServerHasTag;
