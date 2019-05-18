# PDF Merge (Drupal 8)

PDF Merge is a module that allows unlimited number of PDF Files to be merged into a single one.

## Installation
1. Download and install PDFtk library (see http://pdftk.com)
2. Install PDF Merge module

## Usage

At the moment there is no UI for merging file entities.

Provides a function callled pdf\_merge\_multiple which takes file a list of File ID's to merge. 
### Example Usage
```php
pdf_merge_multiple($fids, "new_filename");
```

## Contributors

- Gerald Aryeetey (geraldnda) https://www.drupal.org/u/geraldnda
