import React from 'react';
import App from '@hubleto/react-ui/core/App'
import FormCustomizer from '@hubleto/react-ui/core/FormCustomizer';
import { type FormMeta } from '@hubleto/react-ui/components/fc/FormInterfaces';
import Translator from '@hubleto/react-ui/core/Translator';
import TableServers from "./Components/FC/TableServers"
import TableProviders from "./Components/FC/TableProviders"
import TableDatacenters from "./Components/FC/TableDatacenters"
import TableTags from "./Components/FC/TableTags"
import TableServerHasTags from "./Components/FC/TableServerHasTags"
import TableStackItems from "./Components/FC/TableStackItems"
import TableHealthStatuses from "./Components/FC/TableHealthStatuses"
import TableActivityTemplates from "./Components/FC/TableActivityTemplates"
import TableServerActivities from "./Components/FC/TableServerActivities"
import TableActivityReports from "./Components/FC/TableActivityReports"
import FormServerActivity from "./Components/FC/FormServerActivity"
import ServersPlanner from "./Components/FC/ServersPlanner"

const T = new Translator('Hubleto/App/External/WaiBlue/ServerReport/Loader', 'manifest');

class ServersApp extends App {
  init() {
    super.init();

    // register react components (names must match the tags used in Views/*.twig)
    globalThis.hubleto.registerReactComponent('ServersTableServers', TableServers);
    globalThis.hubleto.registerReactComponent('ServersTableProviders', TableProviders);
    globalThis.hubleto.registerReactComponent('ServersTableDatacenters', TableDatacenters);
    globalThis.hubleto.registerReactComponent('ServersTableTags', TableTags);
    globalThis.hubleto.registerReactComponent('ServersTableServerHasTags', TableServerHasTags);
    globalThis.hubleto.registerReactComponent('ServersTableStackItems', TableStackItems);
    globalThis.hubleto.registerReactComponent('ServersTableHealthStatuses', TableHealthStatuses);
    globalThis.hubleto.registerReactComponent('ServersTableActivityTemplates', TableActivityTemplates);
    globalThis.hubleto.registerReactComponent('ServersTableServerActivities', TableServerActivities);
    globalThis.hubleto.registerReactComponent('ServersTableActivityReports', TableActivityReports);
    globalThis.hubleto.registerReactComponent('ServersPlanner', ServersPlanner);

    // form of an activity of a server, used by the Calendar app (see Calendar.php, 'formComponent')
    globalThis.hubleto.registerReactComponent('ServersFormServerActivity', FormServerActivity);

    // tab "Servers" in the form of a customer
    FormCustomizer.addTab(
      'FormCustomer',
      'server-report',
      (form: FormMeta) => { return form.id <= 0 ? false : {
        title: T.translate('Servers'),
        content: (form: FormMeta) => {
          return <TableServers
            tag={"table_server_customer"}
            parentForm={form}
            uid={form.uid + "_table_server_customer"}
            idCustomer={form.id}
          />;
        }
      }}
    );
  }
}

// register app
globalThis.hubleto.registerApp('Hubleto/App/External/WaiBlue/ServerReport', new ServersApp());
