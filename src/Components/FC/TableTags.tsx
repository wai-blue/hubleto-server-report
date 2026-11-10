import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormTag from './FormTag';

interface TableTagsProps extends TableProps {
}

const componentName = 'TableTags'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableTags = (props: TableTagsProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Tag'}
    baseUrlSlug='server-report/tags'
    formModalProps={{type: 'right wide'}}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormTag {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableTags;
