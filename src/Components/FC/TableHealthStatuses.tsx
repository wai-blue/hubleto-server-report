import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormHealthStatus from './FormHealthStatus';
import HealthBadge from './HealthBadge';

interface TableHealthStatusesProps extends TableProps {
  idServer?: number,
}

const componentName = 'TableHealthStatuses'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableHealthStatuses = (props: TableHealthStatusesProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/HealthStatus'}
    endpointParams={{idServer: props.idServer}}
    baseUrlSlug='server-report/health'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_server: props.idServer}}
    renderCell={(table: TableMeta, columnName: string, column: any, data: any, options: any) => {
      if (columnName == "overall") {
        return <HealthBadge overall={parseInt(data.overall ?? 0)} />;
      } else if (columnName == "status") {
        let summary = '';
        try {
          const status = typeof data.status === 'string' ? JSON.parse(data.status) : data.status;
          if (status && typeof status === 'object') {
            summary = Object.keys(status)
              .filter((key: string) => String(status[key]).trim() !== '')
              .slice(0, 4)
              .map((key: string) => key.replace(/_percent$/, '').replace(/_/g, ' ') + ' ' + status[key])
              .join(' · ');
          }
        } catch (e) {
          summary = '';
        }
        return <span className='text-sm'>{summary}</span>;
      } else {
        return table.renderDefaultCell(columnName, column, data, options);
      }
    }}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormHealthStatus {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableHealthStatuses;
