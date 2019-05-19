## Description
Webform Help Text provides interesting option to show help text for the users which are supposed to fill given form. 
Module adds information icon next to the webform field to wich it is added. 
When user goes over the icon with the pointer tooltip pop-up will be displayed with the text entered into the configuration fom.
The configuration form can be accessed add the webform edit page via a tab next to _'Emails / Handlers'_ , if other modules are not instaled,
or by going to `/admin/structure/webform/manage/{webform}/helptext` 
Access to Help text configuration page can be managed by granting permission provided by the module `Administer Webform Help text` 
to the the user roles you require.

## Requirements
At this moment the only thing you will need to use this module are the `Webform` and `Webform UI` modules.

## Configuration export
Configured Help Text can be exported with the Webform's export as it is stored as component attribute. 

### Project Information
* Drupal project: https://www.drupal.org/project/webform_helptext
* Github project: https://github.com/nikolamitevffw/webform_helptext
