CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Create entity comparison
 * Place the link
 * Set the sort of the fields
 * Place the block
 * Set the permissions
 
INTRODUCTION
------------

You can create comparison pages from a selected entity's bundle with this module.
Your users can add and remove entities to this list, and they can see only their list.

CREATE ENTITY COMPARISON
------------------------

After installing the module, you can create, edit or delete entity comparisons
on the following uri: /admin/structure/entity_comparison (Structure -> Entity comparison)

When you create entity comparison, you have to set the following fields:

  * Label and machine name
  * Text for the link "Add to comparison list"
  * Text for the link to "Remove from the comparison"
  * The limit on the number of compared items ("0" - no limit)
  * Entity
  * Bundle

You can select any content entity's any bundle.

The following things will be generated:
  * A view mode for the related bundle
  * A *"Link for the entity comparison"* field, which type is Entity comparison link.
  * A block which contains a link for the comparison page
  * A permission which allows users to use the created comparison function

PLACE THE LINK
--------------

You can view the newly generated Link for the entity comparison on the selected bundle's view modes.
So you can place it where you want on which view mode you want (for example: Default, Teaser, Full content).
If a content is not in the comparison list, the field will shows a link with the text, 
you added on the entity comparison settings (default: Add to comparison list).

If a content is in the comparison list,  the field will shows a link with the remove text, 
you added on the entity comparison settings (default: Remove from the comparison).

SET THE SORT OF THE FIELDS
--------------------------

A view mode with the identical name is generated after you save the entity comparison 
configuration entity. In this view mode, you can set which fields will be shown, the 
sort of the fields, and the field formatters of the fields.

PLACE THE BLOCK
---------------

A block with the identical name is generated after you save the entity comparison 
configuration entity. It's output is a link to the comparison page. It shows also the 
item numbers of the comparison list.

SET THE PERMISSIONS
-------------------

Permissions are generated dynamically. When you create a new entity comparison 
a permission is generated like this:
[LABEL]: Use entity comparison

[LABEL] is replaced with the label of the entity comparison of course.