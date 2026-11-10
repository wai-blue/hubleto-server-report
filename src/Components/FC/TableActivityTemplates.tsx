import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormActivityTemplate from './FormActivityTemplate';

interface TableActivityTemplatesProps extends TableProps {
}

const componentName = 'TableActivityTemplates'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableActivityTemplates = (props: TableActivityTemplatesProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ActivityTemplate'}
    baseUrlSlug='server-report/activity-templates'
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormActivityTemplate {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableActivityTemplates;
