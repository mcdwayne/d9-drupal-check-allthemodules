### Table Tools Toolbar Plugin

### Description
This plugin exposes buttons in the toolbar that allow managing tables in CKEditor by using the Table Tools Toolbar 
plugin (https://ckeditor.com/cke4/addon/tabletoolstoolbar).

### Installation
1. Download the plugin from https://ckeditor.com/cke4/addon/tabletoolstoolbar
2. Place the plugin in the root libraries folder (/libraries).
3. Enable CKEditor Table Tools Toolbar module in the Drupal admin.

### Usage
Go to the Text formats and editors settings (/admin/config/content/formats) and
add all the table buttons you need to any CKEditor-enabled text format you want.

If you are using the "Limit allowed HTML tags and correct faulty HTML" filter
make sure that Allowed HTML tags include:

```
<table> <caption> <tbody> <thead> <tfoot> <th> <td> <tr>
```

### Contact
Developed and maintained by [Cambrico](http://cambrico.net).

Get in touch with us for customizations and consultancy:
http://cambrico.net/contact

#### Current maintainers:
- Pedro Cambra [(pcambra)](http://drupal.org/user/122101)
- Manuel Egío [(facine)](http://drupal.org/user/1169056)
