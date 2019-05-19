INTRODUCTION
-----------
This module provides a view style plugin, which is capable to insert custom row
with html unrestricted markup or blocks content into a view row results after
every nth row.

The inserted row may contain a standard Drupal block created by views or other
modules or user's content block with custom content. Custom class names along
with the default row classes like views-row, views-row-1 and standard
striping (odd/even), first/last row classes can be applied to inserted and
regular rows.

This module can be used for placing Google Adsense or any other code
snippets/content into your views results.

REQUIREMENTS
------------
Depends on views module.

INSTALLATION
------------
To install this module, do the following:

1. Extract the tar ball that you downloaded from Drupal.org.

2. Upload the entire directory and all its contents to your modules directory.

CONFIGURATION
-------------
To enable and configure this module do the following:

1. Go to Admin -> Modules, and enable Views Rows Insert.

2. Create a view or open an existing view display settings page and click on
   style plugin name next to "Format:" label at the "FORMAT" section.
   Choose "Insert Rows" style plugin in the list and click "Apply".

3. Choose the Row type by selecting "Block" or "Custom content" radio button.
   Depending on your choice, select a block name from the list or fill the
   "Custom content" textarea field. Be careful, this field does not filters out
   your input.

4. Select a number of rows to skip at "Insert after every Nth row" and also
   you can check some other settings like "Start with inserted row",
   "Insert row at the bottom" or "Limit the amount of inserted rows".

5. Enter name of the row class if any and configure other class related
   settings.

CUSTOMIZATION
-------------
To override the default output html markup, you may edit the template file
views-row-insert.html.twig located inside module's templates folder.
