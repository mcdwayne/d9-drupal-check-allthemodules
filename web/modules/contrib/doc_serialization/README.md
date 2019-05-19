# Word (DOCX) Serialization

This module provides a Word encoder for the Drupal 8 Serialization API. This
enables the DOCX format to be used for data output (and potentially input,
eventually). For example:

  * Views can output DOCX data via a 'Word Export' display in a View.
  * Leverages phpword Templates processing.You can upload an OOXML document template with included search-patterns (macros) which can be replaced by any value you wish. Only single-line values can be replaced.
  The search-pattern model is: ${search-pattern}
  * Module developers can leverage DOCX as a format when using the
    Serialization API.

#### Installation

  * Download and install
    [PHPOffice/PHPWord](https://github.com/PHPOffice/PHPWord).
    and all of it's dependencies:
    * [zendframework/zend-escaper 2.4.*](https://github.com/zendframework/zend-escaper/tree/release-2.4.13)
    * [zendframework/zend-stdlib 2.4.*](https://github.com/zendframework/zend-stdlib/tree/release-2.4.13)
    * [zendframework/zend-validator 2.4.*](https://github.com/zendframework/zend-validator/tree/release-2.4.13)
    * [zendframework/zend-stdlib 2.4.*](https://github.com/zendframework/zend-stdlib/tree/release-2.4.13)
    * [phpoffice/common 0.2.6](https://github.com/PHPOffice/Common/tree/0.2.6)
    * [pclzip/pclzip": ^2.8](https://github.com/ivanlanin/pclzip/tree/2.8.2)

    The preferred installation method is to
    [use Composer](https://www.drupal.org/node/2404989).
  * The serialization module is required, so install that too.
  * Enable the `doc_serialization` module.


#### Creating a view with a DOC display

  1. Create a new view
  2. Add a *Word Export* display.
  3. Select 'docx' for the accepted request formats under
     `Format -> Word export -> Settings`.
  4. Add a path, and optionally, a filename (pattern).
  5. Upload the template file with search patterns same as views field names.
  6. Add desired fields to the view.
  7. The view will produce the new doc file with macros replaced by field values.

#### License ####

Unless otherwise stated all code is licensed under GNU GPL v3 and has the following copyright:
```
    Copyright 2017, XSbyte
    All rights reserved
```
