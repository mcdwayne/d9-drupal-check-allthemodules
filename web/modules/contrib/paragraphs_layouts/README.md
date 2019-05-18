[//]: # ( clear&&curl -s -F input_files[]=@README.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )

# PNX Paragraphs layout

Flexbox-based layout styling for paragraphs.

This is a 'feature' module, in that it only bundles up configuration for existing modules.

In particular:

* Configuration for classy paragraphs
* CSS for adding flexbox layout styles

## How to use

1. Install dependencies
2. Install module
3. Create your paragraph types
4. Add the `field_layout_style` field to your paragraph
