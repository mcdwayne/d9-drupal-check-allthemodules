# WIDEN COLLECTIVE DRUPAL MODULE - D8

INTRODUCTION
------------

Widen develop software solutions for marketers who need to connect their visual
content – like graphics, logos, photos, videos, presentations and more –
for greater visibility and brand consistency.

This module allows your Drupal projects to connect to the API of the Digital
Asset Management system Widen Collective with the WYSIWYG CKEditor.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
   for further information.
 * The Widen Community module uses the external library iframedialog for
   ckeditor. Download the plugin from http://ckeditor.com/addon/iframedialog).
 * Place the plugin in the root libraries folder (/libraries). Final path for
   the library should be /libraries/iframedialog/plugin.js


CONFIGURATION
-------------

 * Configure user permissions in Manage » People » Permissions:

   - Administer Widen Collective configuration

     Users in roles with the "Administer Widen Collective configuration"
     permission will be able to enter their access details to Widen Collective.

 * Configure your CKEditor profile in Manage » Configuration » Content authoring
     » Text formats and editors:

  - Plugin to search for and embed Assets from Widen Collective.

    Select text format to edit, drag and drop Widen Collective button to
    one of active toolbar.

 * Configure your access details to Widen Collective in your profile:

   - Widen Collective Authorization

     Enter your username and password to enable your access to Widen Collective.


MAINTAINERS
-----------

Current maintainers:
* Prometsource - https://www.drupal.org/promet-source

This project has been sponsored by:
* WIDEN
  Widen is a content technology company that powers the content that builds
  your brand with our global cloud-based digital asset management solutions.
  Built on more than 65 years of creative workflow experience and 20 years of
  Software as a Service (SaaS), Widen is the trusted leader in Digital Asset
  Management.
