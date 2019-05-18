# Contents of this file
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Using custom forms
 * Issues
 * Maintainers


## Introducction
------------

The Field Group Ajaxified Multipage module makes possible turn a form to
a multipage form ajaxified using groups.

It also provides developers with special variables
attached to form array regarding page status to allow them to further customize
the form depending on the active page.

Also, it works with any forms, even custom ones!

 * For a full description of the module visit:
   https://www.drupal.org/project/field_group_ajaxified_multipage

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/field_group_ajaxified_multipage


## Requirements
------------

 * Field group:

   By using Field Group, it's possible to easily turn an entity form into a
   multipage form.


## Installation
------------

 * Install the Field Group Ajaxified Multipage module using
   "composer require 'drupal/field_group_ajaxified_multipage:<version>'".
   Then enable the module.


## Configuration - Using Field Group
-------------

    1. You'll have two new types of field groups on the displays:

        - Multipage step: Use this type of group to wrapper each field of your
        step.

        - Multipage group: Use this type of group to wrapper each step group
        "Multipage step".
        The options than able the multipage works properly are:
            * Ajaxfied: Yes.
            * Non Javascript Multistep: No.


## Using custom forms
-------------
Field group ajaxified multipage allows to transform custom forms to
multipage ajax forms.

There are a submodule included called "fgam_example" with a full example of
a custom form explained.
Enable the module, then you can see the result in:
"examples/field_group_ajaxified_multipage/custom_form"

The code is in Example.php file.

If you're a developer and like to implement more complex multi-page forms you
can use the following variables defined by the module:

    * "$form_state['field_group_ajaxified_multipage_enabled']" This is sets when
   the form multipage is defined.

    * "$form_state['field_group_ajaxified_multipage_group']" This  contains the
   multipage group.

Sample code for using these variables:
```
function hook_form_alter(&$form, &$form_state, $form_id) {

    if (isset($form_state['field_group_ajaxified_multipage_enabled']) &&
        $form_state['field_group_ajaxified_multipage_enabled']) {

        // Actual number step.
        $step = empty($form_state['storage']['field_group_ajaxified_multipage_step']) ? 1 : $form_state['storage']['field_group_ajaxified_multipage_step'];

        // Main group multipage.
        $page_group = $form_state['field_group_ajaxified_multipage_group'];

        // Best practice for accessing variables, it works even when this ajax
        // grouping is disabled.
        if (isset($form_state['storage']['all']['values'])) {
          $values = $form_state['storage']['all']['values'];
        }
        elseif (isset($form_state['values'])) {
          $values = $form_state['values'];
        }

    }
}
```


## Issues
-------------
The paging can only be done on client side which has several disadvantages.

    1. Validation of form fields is very basic, real validation is done after
       the form is submitted and users will often have to return to previous
       pages to correct the entered values.
    2. More complex multi page forms often dynamically change what the users see
       in next steps depending on the data entered in previous steps. Now that
       the form pages are Ajaxified, there are no limitations.
    3. When the form is complex and has many pages with different fields, it
       can become considerably heavy to load since it uses javascript to
       hide/show related fields on each page, but the whole form is still
       loaded.

FORM API image buttons are not supported by default.


## Maintainers
-----------

 * Sina Salek (http://sina.salek.ws)
 * Sebastian Gurlt (https://www.drupal.org/u/sg88)
 * Eduardo Morales (https://www.drupal.org/u/eduardomadrid)

Supporting organization:

 * Bright Solutions GmbH - https://www.drupal.org/bright-solutions-gmbh
 * Metadrop - https://www.drupal.org/metadrop
