INTRODUCTION
-----------
This is simple view display style plugin, that combines a user defined number
of rows into sets, wrapped by chosen HTML element and attribute.

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

1. Go to Extent (admin/modules), and enable Views Rows Wrapper.

2. Create a view or open an existing view display settings page and click on
   style plugin name next to "Format:" label at the "FORMAT" section.
   Choose "Rows wrapper" style plugin in the list and click Apply.

3. Check "Use this row wrapper" checkbox, select number of rows to wrap
   and also wrapper HTML element with its attribute. Enter name of the chosen
   attribute and select "Apply to all items", if you need to wrap
   all the results. Click "Apply" and check view results.

CUSTOMIZATION
-------------
To override the default output html markup, you may edit the template file
views-rows-wrapper.html.twig located inside module's templates folder.
