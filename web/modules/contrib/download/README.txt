
This module can compress any files attached to a node and provides a 
download link.

Drupal 8 port sponsored by Origin Eight (www.origineight.net)
with dev by greenmachine and ckng

Installation 
------------

1) Install PclZip library

To use this module you have to have the PclZip library
installed by Composer. You can accomplish this either by configuring
Drupal composer or using the Composer Manager extension. See information here:
https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies

2) Place the download folder in the modules directory of your site and
enable it on the `Extend` page.

3) Have at least one entity type that includes at least one file field

4) Add a new Download Link field to that entity type, and select the file fields
that should be included.


Migration from D7
-----------------
A migrate plugin can be found in src/Plugin/migrate/process for an example migration 
scenario from a field in D7 to D8. You will need to modify this file if you want to
use it for your own migration. The example scenario uses these fields:

Drupal 8 Target field : field_download_link
Drupal 7 Source field : download_field

You'll also need to modify this line in the plugin:

$fields = array('field_resourcefiles', 'field_spanish_files', 'field_representative_image');

Replace those array items with the names of the file fields on your contnet type.

Here is the example migrate configuration YML for the example scenario:

process:
  field_download_link:
    plugin: download_field
    source: field_download
    process:
      label: label
      fields: fields
