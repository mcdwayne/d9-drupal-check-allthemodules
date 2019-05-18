
INTRODUCTION
------------
The Drupal 8 Commerce Export module allows you to import Commerce products into
a pre configured commerce site using CSV files. It will import taxonomy terms,
product attribute values, files, product variations and products.
This project is intended to give you a head start on creating you own custom
commerce product import. You will need to create your own custom module, using
the migrations and plugins provided as examples, to import your products.
Thanks to MTech for supporting the development of Migrate Source CSV and the
helpful blogpost, https://www.mtech-llc.com/blog/charlotte-leon/migration-csv-data-paragraphs

REQUIREMENTS
------------
This module requires the following modules:
 * Commerce (https://drupal.org/project/commerce)
 * Migrate Plus (https://drupal.org/project/migrate_plus)
 * Migrate Tools (https://drupal.org/project/migrate_tools)
 * Migrate Source CSV (https://drupal.org/project/migrate_source_csv)

INSTALLATION
------------
Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------
The module has no menu or modifiable settings.

STEPS TO IMPORT
---------------------
* Destination site configuration
* Prepare migration yml files
* Preparing the source CSV
* Upload the source CSV
* Import products

Destination site configuration
------------------------------
* Currently, only default products and default product variation types are
imported. There is no setup required on the destination site, these are setup
by default by the Commerce module.
* Taxonomy. The import will add terms for up to 3 existing vocabularies. The
taxonomies are to be created before running the import.
* Attributes. The import will add attribute values for up to 4 existing
attribute. The attributes must be created before running the import.
* Images. The directory for files is
 <drupal_root>/sites/default/files/images. The files must be in this
 directory and the directory must be accessible to the web server. This path
 can be changed by changing the migration yml files.

Prepare migration yml file
--------------------------
* The migration yml files are examples only and are designed to work with
the test fixture. However, import_taxonomy, import_attribute and import_image
will require less modification than the other migrations.
* Modify the example migration yml files in config/install to match the
configuration of your destination site.
* The 'source' section has an array 'column_names', that will need modification
to match you the columns in your CSV.
* The 'process' section uses destination names for the test environment, these
need to be changed to suit your destination site. Simply remove the existing
fields and add what you need.
* The 'destination' section should not require changes. It is best to leave
these as is and allow the specific entity to do it's own save. If you find
yourself wanting to change or make a new destination plugin, seek out advice
from an experienced migrators. Only change the destination plugin if you really
know what you are doing.

Prepare the source CSV
----------------------
* Begin with the spreadsheet template in example/example.ods
* The example is for one product type and one product variation type. Create a
  new spreadsheet for each product type.
* Each row represents a single product variation.
* Product taxonomy vocabulary and name are in the columns, 'Category 1 name',
  'Category 1 value', 'Category 2 name', 'Category 2 value', 'Category 3 name'
  and 'Category 3 name'. The Category name must match the taxonomy
  vocabularies used on your destination site..
* Product attribute are in the columns 'Attribute 1 name',
   'Attribute 1 value', 'Attribute 2 name', 'Attribute 2 value',
   'Attribute 3 name', 'Attribute 3 value', 'Attribute 4 name' and 'Attribute 4
    value'. The attribute names must exist and match the attribute names on your
    destination site.
* Images. Up to 3 images can be associated with a product variation.
* When satisfied with your changes export it as a CSV with the following
conditions
  * Keep the header row, row 1.
  * Use a field delimiter of comma, ','
  * Use a Text delimiter of double quote, '"'
  * Select option to "Quote all text cells"
  * Save as 'product.csv'

Upload the source CSV
---------------------
* Go to `/admin/structure/migrate`
* Click **Upload**

Import products
---------------
* Go to `admin/structure/migrate/manage/commerce_product_import/migrations`
* Click on **execute** for the row **Products**. It will import everything. See
KNOW PROBLEMS below.

SOURCE PLUGINS
--------------
* All the input values are trimmed before processed.
* Several of the source plugins, such as TaxonomyTerm, make use of yield() to
create the row to be processed. This means that the 3 vocabulary/term pairs on
a single row of the input CSV become 3 separate rows for the migration to
process. This helps to make the input CSV more natural because all the
taxonomy for that product is in the same row.

CHANGING THE MIGRATION FILES
----------------------------
 When changes are made to the migration files, migrate_plus.migration.*.yml,
 then the active configuration must be updated as well. This can be done by
 re-installing the module, or via drush with `drush cex` and `drush cim`
