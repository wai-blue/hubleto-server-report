import React from 'react'
import Translator from '@hubleto/react-ui/core/Translator';
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormActivityReport from './FormActivityReport';

interface TableActivityReportsProps extends TableProps {
  idServer?: number,
  idActivity?: number,
}

const componentName = 'TableActivityReports'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

const TableActivityReports = (props: TableActivityReportsProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ActivityReport'}
    endpointParams={{idServer: props.idServer, idActivity: props.idActivity}}
    baseUrlSlug='server-report/reports'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_server: props.idServer, id_activity: props.idActivity}}
    renderCell={(table: TableMeta, columnName: string, column: any, data: any, options: any) => {
      if (columnName == "followup") {
        return String(data.followup ?? '').trim() !== ''
          ? <span className='badge badge-warning'>{T.translate('Follow-up required')}</span>
          : null;
      } else if (columnName == "result") {
        const result = String(data.result ?? '');
        return <span>{result.length > 80 ? result.substring(0, 80) + '...' : result}</span>;
      } else {
        return table.renderDefaultCell(columnName, column, data, options);
      }
    }}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormActivityReport {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableActivityReports;
