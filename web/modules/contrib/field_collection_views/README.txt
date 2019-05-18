INTRODUCTION
------------
* This module provides a formatter leveraging views for the
  Field Collection module.

INSTALLATION
------------
 * Install contributed Drupal module as we do normally. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

 * After installing this module,then you could goto host entity's fields display
   settings page, such as admin/structure/profiles/manage/resume/display.

 * At display settings page, you could choose format for you collection field,
   This module provide a new option"Views field-collection items"

  * Now if you visit a page of host entity, you could only see three fields,
    " Field collection Item id", "Edit", "Delete",At the bottom there is a
    "Add" link.This is not a bug, that because i do not know which fields
    that added to collection field.

 * Clone views "field_collection_view", at the clone view, you need add more
   fields to it, change the sort criteria,Please do not change the
   existing fields (except "Exclude from display " for
   "Field-collection item: Field-collection item ID"),
   the Contextual filters of " Field-collection item: Field-collection item ID"

 * After that you need config the name /display id of the views you want
   to use at display settings page, such as
   admin/structure/profiles/manage/resume/display,you click the button
   at the right place, then there will be a form inlude 2 elements,"name"
   and "display id".


MAINTAINERS
-----------
Current maintainers:
 * Ahana Kundu (ahana92) - https://www.drupal.org/u/ahana92
 * Mahaveer Singh Panwar - https://www.drupal.org/u/mahaveer003
