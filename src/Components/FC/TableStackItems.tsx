import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormStackItem from './FormStackItem';

interface TableStackItemsProps extends TableProps {
  idServer?: number,
}

const componentName = 'TableStackItems'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableStackItems = (props: TableStackItemsProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/StackItem'}
    endpointParams={{idServer: props.idServer}}
    baseUrlSlug='server-report/stack'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_server: props.idServer}}
    getRowClassName={(table: TableMeta, rowData: any): string => {
      return rowData.is_current ? table.getDefaultRowClassName(rowData) : 'bg-slate-100 text-slate-500';
    }}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormStackItem {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableStackItems;
