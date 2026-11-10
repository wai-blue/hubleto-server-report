import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form from '@hubleto/react-ui/components/fc/Form';

export interface FormTagProps extends FormProps {}

const componentName = 'FormTag'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** FormTag */
const FormTag = (props: FormTagProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Tag'}
    urlSlug='server-report/tags'
    title={{field: 'name', sub: T.translate('Server tag')}}
    {...props}
  ></Form>;
}

export default FormTag;
