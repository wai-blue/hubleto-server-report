import React from 'react'
import Translator from '@hubleto/react-ui/core/Translator';
import request from '@hubleto/react-ui/core/Request';
import Table from '@hubleto/react-ui/components/fc/Table';
import { type TableMeta, type TableProps } from '@hubleto/react-ui/components/fc/TableInterfaces';
import FormServerActivity from './FormServerActivity';

interface TableServerActivitiesProps extends TableProps {
  idServer?: number,
  idTemplate?: number,
}

const componentName = 'TableServerActivities'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

const TableServerActivities = (props: TableServerActivitiesProps) => {
  return <Table
    componentName={componentName}
    parentApp={parentApp}
    model={parentApp + '/Models/ServerActivity'}
    endpointParams={{idServer: props.idServer, idTemplate: props.idTemplate}}
    baseUrlSlug='server-report/activities'
    formModalProps={{type: 'right wide'}}
    formDefaultValues={{id_server: props.idServer, id_template: props.idTemplate}}
    getRowClassName={(table: TableMeta, rowData: any): string => {
      const today = new Date().toISOString().substring(0, 10);
      if (!rowData.completed && !rowData.recurrence && rowData.date_start && rowData.date_start < today) {
        return 'bg-red-50 text-red-800';
      } else if (rowData.completed) {
        return 'bg-slate-100 text-slate-500';
      } else {
        return table.getDefaultRowClassName(rowData);
      }
    }}
    renderCell={(table: TableMeta, columnName: string, column: any, data: any, options: any) => {
      if (columnName == "virt_reports") {
        const count = parseInt(data.virt_reports ?? 0);
        return <div className='flex gap-2' style={{alignItems: 'center'}}>
          <span>{count}</span>
          <button
            className='btn btn-small btn-transparent'
            onClick={(e: any) => {
              e.stopPropagation();
              request.get('server-report/api/create-report-from-activity', {idActivity: data.id}, (res: any) => {
                if (res.status == 'success') table.reload();
                else alert(res.message ?? T.translate('The report could not be created.'));
              });
            }}
          >
            <span className='icon'><i className='fas fa-file-circle-plus'></i></span>
            <span className='text'>{T.translate('Create report')}</span>
          </button>
        </div>;
      } else {
        return table.renderDefaultCell(columnName, column, data, options);
      }
    }}
    renderForm={(table: TableMeta): React.JSX.Element => {
      return <FormServerActivity {...table.getDefaultFormProps()} />;
    }}
    {...props}
  ></Table>
}

export default TableServerActivities;
