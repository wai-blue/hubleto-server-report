import React from 'react'
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormServer from './FormServer';
import HealthBadge from './HealthBadge';

interface TableServersProps extends TableProps {
  idCustomer?: number,
  idDatacenter?: number,
  idProvider?: number,
}

const componentName = 'TableServers'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';

const TableServers = (props: TableServersProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/Server'}
    endpointParams={{idCustomer: props.idCustomer, idDatacenter: props.idDatacenter, idProvider: props.idProvider}}
    baseUrlSlug='server-report'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_customer: props.idCustomer, id_datacenter: props.idDatacenter, id_provider: props.idProvider}}
    getRowClassName={(table: TableMeta, rowData: any): string => {
      return rowData.is_closed ? 'bg-slate-300' : table.getDefaultRowClassName(rowData);
    }}
    renderCell={(table: TableMeta, columnName: string, column: any, data: any, options: any) => {
      if (columnName == "virt_tags") {
        return <div className='flex gap-1'>
          {(data.TAGS ?? []).map((tag: any, key: number) => {
            return <div key={key} className="text-nowrap mr-2">
              <i style={{color: tag.TAG?.color}} className="fas fa-tag mr-2"></i>
              {tag.TAG?.name}
            </div>;
          })}
        </div>;
      } else if (columnName == "virt_health") {
        const overall = data.virt_health === null || data.virt_health === undefined ? 0 : parseInt(data.virt_health);
        return <HealthBadge overall={overall} />;
      } else {
        return table.renderDefaultCell(columnName, column, data, options);
      }
    }}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormServer {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableServers;
