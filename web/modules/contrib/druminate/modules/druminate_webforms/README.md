# Druminate Webforms

The Druminate Webforms module allows editors to send webform submissions to 
[Luminate Online Surveys](http://open.convio.com/api/#survey_api) & [Luminate Online Donations](http://open.convio.com/api/#donation_api),

## Installation

Install as usual.

Place the entirety of this directory in the /modules folder of your Drupal
installation. Navigate to Administer > Extend. Check the 'Enabled' box next
to the 'Druminate' and then click
the 'Save Configuration' button at the bottom.

## Configuration

Navigate to Administer > Configuration > Druminate. Enter all of the
required settings and click the 'Save Configuration' button.

## Usage

### Surveys
1. Create the survey inside of luminate.
1. Create the corresponding webform at Structure > Webforms > Add Webform.
1. Navigate to Settings > Emails/Handlers on the newly created webform.
1. Select Add Handler and then choose Druminate Survey from the list.
1. Select the correct survey id from the drop down and then match the webform
elements to the corresponding survey fields.

### Donation Forms
1. Create the donation form inside of luminate.
1. Create the corresponding webform at Structure > Webforms > Add Webform.
1. Navigate to Settings > Emails/Handlers on the newly created webform.
1. Select Add Handler and then choose Druminate Donation from the list.
1. Select the correct survey id from the drop down and then match the webform
elements to the corresponding survey fields.

*NOTE*
To maintain PCI data security standards for payment card transactions,
all donation calls must be made from the client's browser. Because of
this all Druminate Donations are submitted using JS.

This means that submissions from these forms will not be logged in drupal
and the only Confirmation Types available are page and url.
