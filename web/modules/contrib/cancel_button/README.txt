Entity Form Cancel Button
-------------------------

This module allows site administrators to enable a cancel button on entity
forms.


INTRODUCTION
------------

The destination where the cancel button takes the user varies based on the
context.

 * If the form itself has a redirect set internally (through
   FormState::setRedirect(), often used in form submit handler code), the
   cancel button will direct the user to the form's redirect destination.

 * If there is no internal form redirect in the form's submit handler code, but
   there is a destination parameter in the URL
   (https://www.example.com/node/1/edit?destination=/admin/content), the cancel
   button will direct the user to the path in the destination URL parameter.

 * If there is no destination parameter in the URL, and this is the standalone
   edit form page for the entity
   (for example, https://www.example.com/node/1/edit), the cancel button will
   direct the user to one of the following pages, in this order of precedence:

   - If the entity has a canonical page defined (a standalone 'view' page for
     the entity, for example https://www.example.com/node/1), then the cancel
     button will direct the user there.

   - If a canonical page for the entity cannot be found, but the entity type
     has a 'collection' page defined (for example,
     https://www.example.com/admin/structure/types for a Content Type
     configuration entity), then the cancel button will direct the user to the
     collection page.

   - If there is neither a canonical page nor a collection page available for
     the entity, and the form is for a field configuration, then the cancel
     button will direct the user to page that lists the fields for the bundle
     on which the current field appears.

 * If none of the above are available, the module looks at the Referer in the
   HTTP headers to see which page the user was viewing prior to accessing the
   form.

 * If there is no HTTP Referer available (for example, if the user typed the
   URL to the form directly into the browser, or accessed the form from a
   browser bookmark), then the user is directed to default cancel destination
   configured per entity type/bundle at /admin/config/content/cancel-button.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Enable the module.

 * Visit /admin/config/content/cancel-button and select the entity types
   on which to enable the cancel button.

 * Select the default cancel destination for each entity type/bundle (for cases
   when the user manually typed the URL of the form directly into the browser,
   or accessed the form from a bookmark).

 * Save the configuration.

 * You're done!
