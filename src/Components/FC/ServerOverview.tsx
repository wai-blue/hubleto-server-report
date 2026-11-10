import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import request from '@hubleto/react-ui/core/Request';
import HealthBadge, { healthColor } from './HealthBadge';

const componentName = 'ServerOverview'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

interface ServerOverviewProps {
  idServer: number,
}

// height of the bars of the health timeline, by overall status
const TIMELINE_HEIGHT: { [overall: number]: number } = {0: 8, 1: 12, 2: 22, 3: 32, 4: 40};

/** Small card with a number and a caption. */
const Counter = (props: { value: any, caption: string, color: string }) => {
  return <div style={{borderLeft: '6px solid ' + props.color, padding: '2px 12px', minWidth: '90px'}}>
    <div style={{fontSize: '1.5em', fontWeight: 600}}>{props.value}</div>
    <div className='text-sm'>{props.caption}</div>
  </div>;
}

/** Overview dashboard of one server: current health, metrics, health timeline, activities and stack. */
const ServerOverview = (props: ServerOverviewProps) => {
  const [data, setData] = React.useState<any>(null);
  const [error, setError] = React.useState<string>('');

  const load = () => {
    request.get('server-report/api/get-server-overview', {idServer: props.idServer}, (res: any) => {
      if (res.status == 'success') {
        setData(res);
        setError('');
      } else {
        setError(res.message ?? T.translate('The overview could not be loaded.'));
      }
    });
  };

  React.useEffect(() => { load(); }, [props.idServer]);

  if (error) return <div className='alert alert-info mb-2'>{error}</div>;
  if (!data) return <div className='mb-2'>{T.translate('Loading...')}</div>;

  const health = data.health;
  const history: Array<any> = data.history ?? [];
  const metrics: { [key: string]: number } = data.metrics ?? {};
  const barWidth = history.length > 0 ? Math.max(4, Math.min(16, Math.floor(480 / history.length))) : 8;
  const timelineWidth = Math.max(1, history.length) * (barWidth + 2);

  return <div className='mb-2' style={{border: '1px solid #e5e7eb', borderRadius: '6px', padding: '8px 12px'}}>
    <div className='flex gap-2 flex-wrap' style={{alignItems: 'center'}}>
      <HealthBadge overall={health ? health.overall : 0} />
      <span className='text-sm'>
        {health
          ? T.translate('Last check') + ': ' + health.checked_at
          : T.translate('No health check has been recorded yet.')}
      </span>
      {health && health.stale
        ? <span className='text-sm' style={{color: '#dc2626'}}>({T.translate('no recent check')})</span>
        : null}
      {data.server.workflow_step
        ? <span className='text-sm' style={{marginLeft: 'auto'}}>
          <i className='fas fa-diagram-project mr-1' style={{color: data.server.workflow_step_color || '#6b7280'}}></i>
          {data.server.workflow_step}
        </span>
        : null}
    </div>

    {health && health.note ? <div className='text-sm mt-1'>{health.note}</div> : null}
    {data.server.location || data.server.customer
      ? <div className='text-sm mt-1' style={{color: '#6b7280'}}>
        {[data.server.customer, data.server.location].filter((v: string) => v).join(' · ')}
      </div>
      : null}

    {Object.keys(metrics).length > 0 ? <div className='flex gap-4 flex-wrap mt-2'>
      {Object.keys(metrics).map((key: string) => {
        const value = metrics[key];
        if (key.endsWith('_percent')) {
          const color = value >= 90 ? '#dc2626' : (value >= 75 ? '#ea580c' : '#16a34a');
          return <div key={key} style={{minWidth: '150px'}}>
            <div className='text-xs'>{key.replace(/_percent$/, '').replace(/_/g, ' ')}: {value} %</div>
            <div style={{background: '#e5e7eb', borderRadius: '3px', height: '8px'}}>
              <div style={{background: color, width: Math.min(value, 100) + '%', height: '8px', borderRadius: '3px'}}></div>
            </div>
          </div>;
        } else {
          return <div key={key} className='text-xs'>{key.replace(/_/g, ' ')}: <b>{value}</b></div>;
        }
      })}
    </div> : null}

    {history.length > 0 ? <div className='mt-2'>
      <div className='text-xs' style={{color: '#6b7280'}}>{T.translate('Health checks (oldest to newest)')}</div>
      <svg width={timelineWidth} height={44} role='img' style={{maxWidth: '100%'}}>
        {history.map((item: any, index: number) => {
          const height = TIMELINE_HEIGHT[item.overall] ?? 8;
          return <rect
            key={item.id}
            x={index * (barWidth + 2)}
            y={44 - height}
            width={barWidth}
            height={height}
            rx={2}
            fill={healthColor(item.overall)}
          ><title>{item.checked_at + ' - ' + item.label}</title></rect>;
        })}
      </svg>
    </div> : null}

    <div className='flex gap-3 flex-wrap mt-3'>
      <Counter value={data.upcoming.length > 0 ? data.upcoming[0].date : '-'} caption={T.translate('next activity')} color='#0ea5e9' />
      <Counter value={data.overdue} caption={T.translate('overdue activities')} color={data.overdue > 0 ? '#dc2626' : '#6b7280'} />
      <Counter
        value={data.counts.reports_period}
        caption={T.translate('reports in the last') + ' ' + data.counts.period_days + ' ' + T.translate('days')}
        color='#16a34a'
      />
      <Counter value={data.stack.length} caption={T.translate('applications in the stack')} color='#7c3aed' />
    </div>

    <div className='flex-dyn gap-4 mt-3'>
      <div className='flex-1'>
        <div className='font-bold text-sm mb-1'>{T.translate('Upcoming activities')}</div>
        {data.upcoming.length == 0 ? <div className='text-sm' style={{color: '#6b7280'}}>{T.translate('Nothing is planned.')}</div> : null}
        {data.upcoming.map((item: any) => <div key={item.id} className='text-sm'>
          <b>{item.date}</b> {item.time} {item.subject}
        </div>)}
      </div>
      <div className='flex-1'>
        <div className='font-bold text-sm mb-1'>{T.translate('Recent reports')}</div>
        {data.recent_reports.length == 0 ? <div className='text-sm' style={{color: '#6b7280'}}>{T.translate('No reports yet.')}</div> : null}
        {data.recent_reports.map((item: any) => <div key={item.id} className='text-sm'>
          <b>{item.date}</b> {item.subject}{item.worker ? ' (' + item.worker + ')' : ''}
          {item.has_followup ? <span style={{color: '#b45309'}}> · {T.translate('follow-up required')}</span> : null}
        </div>)}
      </div>
    </div>

    {data.stack.length > 0 ? <div className='flex gap-2 flex-wrap mt-3'>
      {data.stack.map((item: any) => <span
        key={item.id}
        className='text-sm'
        style={{background: '#f3f4f6', borderRadius: '4px', padding: '1px 8px'}}
      >{item.name}{item.version ? ' ' + item.version : ''}</span>)}
    </div> : null}

    <div className='mt-2'>
      <button className='btn btn-small btn-transparent' onClick={load}>
        <span className='icon'><i className='fas fa-rotate'></i></span>
        <span className='text'>{T.translate('Reload overview')}</span>
      </button>
    </div>
  </div>;
}

export default ServerOverview;
