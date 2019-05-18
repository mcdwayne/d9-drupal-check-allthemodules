Description:
------------
This module extends the webform module to easily allow submitting a Salesforce
web-to-lead form. This is important for Salesforce users who are not currently
subscribed to either their Enterprise, Developer or Unlimited editions.

Dependencies:
------------
Webform 5.x

Installation:
------------
Can be installed with drush or composer. No composer dependencies.

Configuration:
------------
At first you will need to login to your Salesforce Account and create a
web-to-lead form. This is accessible from Setup>Customize>Leads>Web-to-Lead.

You should now be on the Web-to-Lead Setup page. Simply click on the 'Create
web-to-lead button' and select the fields that you would like to have available
on your website. Once you have finished hit the 'Generate' button and you should
be taken to a page where you can copy the form that Salesforce has generated to
your webpage. You can determine your Salesforce OID and the names of the fields
by looking at the HTML that has been generated.

Edit the webform you wish to send data to Salesforce. On the
"Emails / Handlers" tab, use "Add handler" to add a "Salesforce Web-to-Lead
post" handler.

Fill in required fields on the handler configuration, including OID and 
Salesforce URL. Custom data may be used as well: enter YAML data that will
override any mapping configuration. Keep in mind:
* key values must match the generated web2lead input names, not the Salesforce
field names
* token values must be single-quoted to generate valid YAML

Use the "Webform to Salesforce mapping" fields to assign Webform fields to your
Lead fields.

Thats it. Once you are done, you can submit your form and see if it populates
Salesforce.

One tip is to "enable debugging" field in your Webform to allow you to be
emailed once the data has been submitted.
