
Node Form API Fields Module Readme
----------------------------------


Installation
------------

To install this module, place it in your modules folder and enable it on the
modules page. You must then implement this module's hook for this module to 
take effect.


Configuration
-------------

All settings for this module are on the Node Form API Fields configuration page, 
under the Configuration section, in the Content authoring settings. You can 
visit the configuration page directly at 
admin/config/content/node_form_api_fields. You can decide whether you would
like to have your fields automatically placed in a fieldset for you or whether
you would like to control that yourself in your code.


What this module does and does not do
-------------------------------------

This module allows you to use a single hook to easily extend the node edit form 
using any elements from the Form API 
(https://api.drupal.org/api/drupal/elements). It automatically saves the 
contents of those fields using the Drupal 8 Key Value storage (rather than 
fields). The saved data for the fields is made available on the node object 
within $node->form_api_fields.

This module does not add any fields for you and does not provide an interface 
for you to add fields. If you do not implement 
hook_node_form_api_fields_form_alter(), this module does nothing.


When is this module useful
--------------------------

If you want to attach many fields (ie, 100s of fields) to many specific nodes 
but don't want your database to grow extensively by doing so, you could use this 
module.

Alternative approaches to avoiding large numbers of field tables could be:
 
 * Using a different storage engine such as MongoDB

 * Put your single use fields into blocks and attach the blocks to nodes to
   avoid adding the additional fields overhead to every node

 * Would welcome feedback or ideas on other approaches here


Feedback on this module
-----------------------

At the moment, I am not 100% confident the Key Value store is the best place
for this and would appreciate feedback. I believe the configuration storage 
is not the right place as this would generally be used for 1-off fields added 
to particular nodes and as such are to do with the state of the site rather 
than the configuration of the site.