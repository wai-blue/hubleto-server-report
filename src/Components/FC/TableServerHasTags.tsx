import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormServerHasTag from './FormServerHasTag';

interface TableServerHasTagsProps extends TableProps {
  idServer?: number,
  idTag?: number,
}

const componentName = 'TableServerHasTags'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableServerHasTags = (props: TableServerHasTagsProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ServerHasTag'}
    endpointParams={{idServer: props.idServer, idTag: props.idTag}}
    baseUrlSlug='server-report/server-has-tags'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_server: props.idServer, id_tag: props.idTag}}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormServerHasTag {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableServerHasTags;
