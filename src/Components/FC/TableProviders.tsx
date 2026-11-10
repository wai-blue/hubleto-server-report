import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormProvider from './FormProvider';

interface TableProvidersProps extends TableProps {
}

const componentName = 'TableProviders'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableProviders = (props: TableProvidersProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Provider'}
    baseUrlSlug='server-report/providers'
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormProvider {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableProviders;
