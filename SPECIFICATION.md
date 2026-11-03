# Specification of the app

* Main goal: To help IT teams keep track of current status of servers they manage.
* Target user: IT manager, system administrators, server managers
* Key functionality:
  * keep track of servers managed in the team
    * description of the server
    * details about how to connect to the server, do not store passwords
    * boolean column "is_closed"
    * application stack with history (this should be a separated table linked by 1:N lookup), for each app keep track of used version (varchar) and its configuration (JSON)
    * health status with history (this should be a separated table linked by 1:N lookup), status stored as JSON
    * designate responsible administrator and manager of the server
    * location of the server - including address of the datacenter and location of the server in the data center
    * provider of the HW - address, contact details, invoicing details (create separate table of these providers and link them to the model of server)
    * link to customer for which the server is managed
    * description of the server
    * workflow management (id_workflow and id_workflow_step columns) with typical steps created during app's installation
    * notes
    * default tab of Form.tsx of the server should also contain some overview dashboard or charts or similar
    * button to generate server report into PDF, same principle as in app Invoices (uses app Documents to generate PDF from template)
      * create default template for server report during app's installation
      * server report should contain: details about the server, datetime of report generation, details about report current health status and activities performed on the server since last 90 days
  * plan regular activities like, e.g. monthly updates, weekly manual health monitor check or other activities that typically need to be performed by system administators
    * navigation UI to navigate to create/update plan of different types of typical activities
    * link each acctivity with the selected server
    * add Calendar tab to FormServer.tsx with the same functionality as in FormDeal.tsx or FormOrder.tsx
  * table to store reports on each planned activity linked to the particular planned activity in the calendar
    * link to the activity in calendar
    * hardcopy of activity's date, time, description, who worked on it and on which server it was performed
    * text to describe steps performed
    * text to describe the result
    * text to describe required followup activities
  * overall dashboard showing current overall health status of all servers currently managed by current user or a configured user
  * detailed dashboard showing current detailed health status and activities performed since last X days, where X is configurable with default value 14 days
  * sidebar and app menu enabling intuitive navigation
