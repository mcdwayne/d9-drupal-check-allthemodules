# Twig Backlink
The Twig Backlink Twig extension builds a list of URL of the parents of the node based on the field name passed in the Twig _twig_backlink_ extension parameter.

Usage:
```twig
{{ twig_backlink('field_name') }}
```

This extension will automatically build a list of parents when an Entity Reference field is used and referenced in the Twig file.

## Requirements
- An Entity Reference field added to a node.
- Access to a node twig file that the themer can edit.

