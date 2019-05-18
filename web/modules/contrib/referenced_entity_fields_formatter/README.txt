README.txt
==========

This module lets you use the rendered custom fields of a entity reference revision in the twig template of the
referencing entity.

For example you have 2 paragraph types:
- Tabs
    - ERR to Tab Paragraph
- Tab
    - Title
    - Body

With this module used as a formatter for the ERR field o Tabs you can use the title and body fields of Tab on the Tabs
Template. the fields are rendered and the values can be accessed for example with twig field_value.

ROADMAP
==========

- Allow for entity reference
- Automated testing
- Code Style Cleanups
