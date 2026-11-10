import React from 'react';
import Translator from '@hubleto/react-ui/core/Translator';
import request from '@hubleto/react-ui/core/Request';

const componentName = 'ServersPlanner'; // must be the same as the exported const
const parentApp = 'Hubleto/App/External/WaiBlue/ServerReport';
const T = new Translator(parentApp + '/Loader', 'Components/' + componentName);

/** Planner: typical activities of system administrators with buttons to plan them on servers. */
const ServersPlanner = (props: any) => {
  const projectUrl: string = globalThis.hubleto.config.projectUrl;
  const [data, setData] = React.useState<any>(null);
  const [error, setError] = React.useState<string>('');
  const [message, setMessage] = React.useState<string>('');
  const [selected, setSelected] = React.useState<any>(null);
  const [idServers, setIdServers] = React.useState<Array<number>>([]);
  const [dateStart, setDateStart] = React.useState<string>(new Date().toISOString().substring(0, 10));
  const [timeStart, setTimeStart] = React.useState<string>('');
  const [saving, setSaving] = React.useState<boolean>(false);

  const load = () => {
    request.get('server-report/api/get-planner-data', {}, (res: any) => {
      if (res.status == 'success') {
        setData(res);
        setError('');
      } else {
        setError(res.message ?? T.translate('The planner could not be loaded.'));
      }
    });
  };

  React.useEffect(() => { load(); }, []);

  const startPlanning = (template: any) => {
    setSelected(template);
    setIdServers([]);
    setTimeStart(template.default_time ?? '');
    setMessage('');
  };

  const toggleServer = (id: number) => {
    setIdServers(idServers.includes(id) ? idServers.filter((i: number) => i != id) : [...idServers, id]);
  };

  const plan = () => {
    setSaving(true);
    request.get(
      'server-report/api/plan-activities',
      {idTemplate: selected.id, idServers: idServers.join(','), dateStart: dateStart, timeStart: timeStart},
      (res: any) => {
        setSaving(false);
        if (res.status == 'success') {
          setMessage(res.created + ' ' + T.translate('activities were planned.'));
          setSelected(null);
          load();
        } else {
          alert(res.message ?? T.translate('The activities could not be planned.'));
        }
      }
    );
  };

  if (error) return <div className='alert alert-info'>{error}</div>;
  if (!data) return <div>{T.translate('Loading...')}</div>;

  return <div className='flex flex-col gap-3'>
    {message ? <div className='alert alert-info'>
      {message} <a href={projectUrl + '/calendar?show=server-report'}>{T.translate('Open the calendar')}</a>
    </div> : null}

    <div className='flex gap-3 flex-wrap'>
      {data.templates.length == 0
        ? <div className='alert alert-info'>
          {T.translate('There are no typical activities yet.')} <a href={projectUrl + '/server-report/activity-templates/add'}>{T.translate('Add one')}</a>
        </div>
        : null}
      {data.templates.map((template: any) => <div
        key={template.id}
        style={{border: '1px solid #e5e7eb', borderTop: '5px solid ' + template.color, borderRadius: '6px', padding: '10px 14px', width: '320px'}}
      >
        <div className='font-bold'>{template.name}</div>
        <div className='text-xs' style={{color: '#6b7280'}}>
          {template.type ? template.type + ' · ' : ''}{template.repeats}
          {template.duration_minutes > 0 ? ' · ' + template.duration_minutes + ' min' : ''}
        </div>
        {template.description ? <div className='text-sm mt-1' style={{whiteSpace: 'pre-line', maxHeight: '6em', overflow: 'hidden'}}>
          {template.description}
        </div> : null}
        <div className='flex gap-3 mt-2 text-sm'>
          <span>{T.translate('planned')}: <b>{template.planned}</b></span>
          <span>{T.translate('upcoming')}: <b>{template.upcoming}</b></span>
          <span style={{color: template.overdue > 0 ? '#dc2626' : undefined}}>{T.translate('overdue')}: <b>{template.overdue}</b></span>
        </div>
        <div className='flex gap-2 flex-wrap mt-2'>
          <button className='btn btn-small btn-primary' onClick={() => startPlanning(template)}>
            <span className='icon'><i className='fas fa-calendar-plus'></i></span>
            <span className='text'>{T.translate('Plan')}</span>
          </button>
          <a className='btn btn-small btn-transparent' href={projectUrl + '/server-report/activities'}>
            <span className='icon'><i className='fas fa-list-check'></i></span>
            <span className='text'>{T.translate('Plans')}</span>
          </a>
          <a className='btn btn-small btn-transparent' href={projectUrl + '/server-report/activity-templates/' + template.id}>
            <span className='icon'><i className='fas fa-pen'></i></span>
            <span className='text'>{T.translate('Edit')}</span>
          </a>
        </div>
      </div>)}
    </div>

    {selected ? <div style={{border: '2px solid #0ea5e9', borderRadius: '6px', padding: '12px 16px'}}>
      <div className='font-bold mb-2'>{T.translate('Plan')}: {selected.name}</div>
      <div className='flex gap-4 flex-wrap mb-2'>
        <label className='text-sm'>
          {T.translate('Start date')}<br/>
          <input type='date' value={dateStart} onChange={(e: any) => setDateStart(e.target.value)} />
        </label>
        <label className='text-sm'>
          {T.translate('Start time')}<br/>
          <input type='time' value={timeStart} onChange={(e: any) => setTimeStart(e.target.value)} />
        </label>
        <div className='text-sm' style={{alignSelf: 'flex-end'}}>{selected.repeats}</div>
      </div>
      <div className='text-sm mb-1'>
        {T.translate('Servers')} ({idServers.length} {T.translate('selected')})
        {' '}
        <a href='#' onClick={(e: any) => { e.preventDefault(); setIdServers(data.servers.map((s: any) => s.id)); }}>{T.translate('select all')}</a>
        {' / '}
        <a href='#' onClick={(e: any) => { e.preventDefault(); setIdServers([]); }}>{T.translate('select none')}</a>
      </div>
      <div className='flex gap-3 flex-wrap mb-2' style={{maxHeight: '12em', overflow: 'auto'}}>
        {data.servers.map((server: any) => <label key={server.id} className='text-sm' style={{minWidth: '160px'}}>
          <input type='checkbox' checked={idServers.includes(server.id)} onChange={() => toggleServer(server.id)} /> {server.name}
        </label>)}
      </div>
      <div className='flex gap-2'>
        <button className='btn btn-primary' disabled={saving || idServers.length == 0 || !dateStart} onClick={plan}>
          <span className='icon'><i className='fas fa-check'></i></span>
          <span className='text'>{saving ? T.translate('Planning...') : T.translate('Plan activities')}</span>
        </button>
        <button className='btn btn-transparent' onClick={() => setSelected(null)}>
          <span className='text'>{T.translate('Cancel')}</span>
        </button>
      </div>
    </div> : null}
  </div>;
}

export default ServersPlanner;
