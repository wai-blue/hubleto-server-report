import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form from '@hubleto/react-ui/components/fc/Form';

export interface FormActivityTemplateProps extends FormProps {}

const componentName = 'FormActivityTemplate'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** FormActivityTemplate */
const FormActivityTemplate = (props: FormActivityTemplateProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ActivityTemplate'}
    urlSlug='server-report/activity-templates'
    title={{field: 'name', sub: T.translate('Typical activity')}}
    {...props}
  ></Form>;
}

export default FormActivityTemplate;
