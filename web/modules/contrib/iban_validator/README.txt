Description
This module, based on PHP-IBAN library, implements a validation plugin for Field
validation module.

Dependencies
- Libraries API
- Field validation
- PHP-IBAN (external library)

Library installation
1. Download ZIP archive from https://github.com/globalcitizen/php-iban;
2. Extract all files contained in the project folder to
   [/sites/all]/libraries/php-iban/ Drupal folder.

Usage example
1. Open the "Structure" administration page;
2. Go to "Content types" section and "Manage fields" to extend your entity
   (node, user, etc.);
3. Create a plain text field to store IBAN;
4. Go to "Field validation" section and add and configure a validation rule by
   selecting "IBAN" as validator.

Credits
Howard Ge (https://www.drupal.org/user/174740)
Walter Stanish (https://github.com/globalcitizen)
