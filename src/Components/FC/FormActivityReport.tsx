import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import { type FormProps } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Form from '@hubleto/react-ui/components/fc/Form';

export interface FormActivityReportProps extends FormProps {}

const componentName = 'FormActivityReport'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** FormActivityReport */
const FormActivityReport = (props: FormActivityReportProps) => {
  return <Form
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ActivityReport'}
    urlSlug='server-report/reports'
    title={{fields: ['activity_date', 'activity_subject'], sub: T.translate('Activity report')}}
    {...props}
  ></Form>;
}

export default FormActivityReport;
