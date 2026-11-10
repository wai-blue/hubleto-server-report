import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormDatacenter from './FormDatacenter';

interface TableDatacentersProps extends TableProps {
}

const componentName = 'TableDatacenters'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableDatacenters = (props: TableDatacentersProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Datacenter'}
    baseUrlSlug='server-report/datacenters'
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormDatacenter {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableDatacenters;
