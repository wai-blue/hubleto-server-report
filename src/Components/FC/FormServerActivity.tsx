import CalendarFormActivity from '@hubleto/apps/Calendar/Components/FC/CalendarFormActivity';

/**
 * Form of an activity of a server. It is the form of the Calendar app (with recurrence, type, times, ...)
 * extended with the server. Used by the table of activities, by the Calendar tab of the server and by the global calendar.
 */
const FormServerActivity = (props: any) => {
  return <CalendarFormActivity
    id={props.id}
    calendarTab={props.calendarTab}
    customInputFields={['id_server', 'id_template']}
    defaultValues={{...props.defaultValues, ...(props.idServer ? {id_server: props.idServer} : {})}}
    model='Hubleto/App/External/WaiBlue/ServerReport/Models/ServerActivity'
    onClose={props.onClose}
  ></CalendarFormActivity>
}

export default FormServerActivity;
