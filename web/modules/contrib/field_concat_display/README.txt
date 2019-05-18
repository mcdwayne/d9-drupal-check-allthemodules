INTRODUCTION TO FIELD CONCAT DISPLAY
------------------------------------
  This module provides an interface for concatenating 2 or more fields
  together and displaying the fields *without creating new field tables.*

INSTALLATION
------------
  Install as you would normally install a contributed drupal module. See:
  https://drupal.org/documentation/install/modules-themes/modules-7
  for further information.

CONFIGURATION
-------------
  Navigate to admin/structure/types/manage/*/concat_fields
  (where "*" is the content type that you want to concatenate fields)

  Enter a name for your new concatenated field (alphanumerics and underscores
  only).

  Check the boxes next to the fields you wish to concatenate together.

  Click "Save New Field"

  After the page reloads, you should see a new table appear.  In this table you
  can set the prefixes/suffixes and weights for each field you selected to be
  concatenated together.

  The prefix/suffix textfields may be left blank, but the weight textfields must
  be set up correctly for your concatenated field to be displayed correctly.

  Starting with zero, set the weights... zero will be in the left-most position.

  Click "Update [your field name]" to save any changes to your prefixes/suffixes
  and weights.

  You may add more concatenated fields to this content type by using the
  "Create a new field" section.

  Fields can also be removed by clicking "Remove [your field name]."

  Fields are stored on a per content type basis, so you will only see
  the edit tables for a field on it's associated content type's "Field
  Concat Display" tab.

  After you've set up a field, adjusted the weights and clicked 
  "Update," your concatenated field is ready to go. Pick a piece of
  content of the same type as the one you added the new field to and 
  view it - you should be able to see the original field values
  displayed in order according to your weight values.
  
  Any prefixes/suffixes should also be attached to their respective
  fields.

  ***The module does NOT separate prefixes, suffixes or fields with
  blank space by default. If you wish to separate the various pieces,
  simply add a separator character in the appropriate suffix or prefix
  textfield.

  If you wish to order where your new field(s) are displayed in
  relation to the other fields on the content type, navigate to
  admin/structure/types/manage/*/display. You should see your new 
  field(s) in the list. This form will allow you to drag the 
  fields in the list and reorder them. You can also hide fields
  here, and you may wish to hide the original fields so you don't
  display the same data twice.

  If you navigate to admin/structure/types/manage/*/fields, your 
  new field(s) will also appear in this list. Again, you can reorder
  everything here. A "delete" link is also supplied which will delete
  that specific field.

MAINTAINERS
-----------
  Jay Schoen - https://www.drupal.org/user/2529276

  Sponsored by
    NumbersUSA
