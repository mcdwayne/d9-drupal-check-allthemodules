TABLE FIELD GROUP
=================

Extends <a href="https://drupal.org/project/field_group">Field Group</a> module with an option to make a 'Table' group for viewing mode.
Children fields will be displayed in per columns look

##Dependency
<a href="https://drupal.org/project/field_group">Field Group</a>

##Installing
Like a normal Drupal module, copy folder in the right place and activate it.  
Best practice for installing is <a href="https://www.drupal.org/node/1897420">here</a>.

With composer:

    composer require drupal/table_field_group

##Using
Just create a group of type: 'Table'.
In the group settings, you can wrap the table in a
* container: invisible wrapper
* fieldset: normal wrapper
* details: collapsible wrapper

