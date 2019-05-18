Field Load More for Developers
=======================

Project site: http://drupal.org/project/field_load_more

Code: https://drupal.org/project/field_load_more/git-instructions

Issues: https://drupal.org/project/issues/field_load_more

What Is This?
-------------

This module provides a feature for fields with unlimited cardinality, through
which administrator can initially show a specified number of items, and load more
button to display rest of the elements based on the count mentioned by him.


How To Use Field Load More
-----------------------

1. Download and Enable the module.

2. Go to Manage Display tab of the content type, and click settings (gear) icon
of any field with unlimited cardinality.

3. You will see a checkbox for "Enable Load more widget for the field", enable it.

4. Enter the initial number of items, which will be displayed under
"Number of items to display by default". Please note that on clicking "Load More"
button, no of items which will load, will depend on the number which you have
entered. If you have entered 5 in above textfield for image field, then initially
5 image will be shown, and on clicking on "Load More", 5 more will be shown.

5. Click on update, save the display.


How To Install The Modules
--------------------------

1. The Field Load More project installs like any other Drupal module. There is extensive
documentation on how to do this here:
https://drupal.org/documentation/install/modules-themes/modules-8 But essentially:
Download the tarball and expand it into the modules/ directory in your Drupal 8
installation.

2. Within Drupal, enable any Field Load More module in Admin
menu > Extend.

3. Settings of Field Load More will show in Manage Display tab for individual content
type, which have multivalued field, with unlimited cadinality.


Thanks.

