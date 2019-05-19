UNIQUE CODE FIELD
-----------------

This module adds a new Unique Code field type to your Drupal project. Can be
used with all entity type.

SETTINGS
--------
The available settings for this field let the user choose the generated code
length and type.

Available code types are:
- Alphanumerical (letters and numbers)
- Numeric (numbers only)
- Alphabetical (letters only)

Each code is generated using pragmarx/random library.

USAGE
-----
Usage is quite simple and mimic the behavior of the every other field in Drupal.
You just have to add a new field to your entity\content and choose Unique Code
from dropdown.
The module will perform a check in the field parent entity to ensure that the
generated code is really unique.
To prevent potentially armful string the field is sanitized using
Html::escape() method from Drupal.

IMPORTANT
---------
If you have downloaded the compressed package, remember that this module requires pragmarx/random library. You should install it wither via Composer or as you want.

TODO
----
This is a list of the implementations that will soon come for this module:

- A Date&Time based code type