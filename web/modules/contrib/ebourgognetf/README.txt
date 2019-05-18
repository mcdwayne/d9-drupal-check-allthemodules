
-- INTRODUCTION --

This plugin allows you to add links to your webforms created using 
"Mes démarches" from e-bourgogne (http://www.e-bourgogne.fr).

For a full description of the module, visit the project page:
  http://drupal.org/project/ebourgognetf
To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/ebourgognetf

-- REQUIREMENTS --

This module requires the following modules:
* CKEditor (https://www.drupal.org/project/ckeditor)


-- INSTALLATION --

* Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8
for further information.


-- CONFIGURATION --

* Go to Administration » Modules et open the module's configuration page.
Fill in your API key (given by your organisation's administration)
and click on "Save"; if your key is valid, you will see the list of
webforms available for your organisation.

* Go to
Administration » Configuration » Content authoring » CKEditor
and, for each profil you want to add the Webforms button to:

  - click on "edit" for the selected profile

  - open "Editor Appearance"

  - drag and drop the button "tf_link" from
  "Available buttons" to "Current toolbar"

  - on the same page, go to the menu "Plugins" and check the line
  "Tf_link: enable e-bourgogne webforms links"

* You can now add links to your webforms from
the content editor using this new button.


-- FAQ --

Q: Where can I find my API key?
A: The API key that allows you to access e-bourgogne services is given
to you by your organisation's administrator.

Q: How can I configure my webforms?
A: Configuring the webforms can be done in e-bourgogne
(http://www.e-bourgogne.fr), service "Mon Service Public » Mes démarches"

-- MAINTAINERS --

Current maintainers :
GIP e-bourgogne