## Description
Default Paragraphs module provides a widget for entity_reference_revisions
field types, that allows us to select multiple paragraph types as defaults, so
they will be added on the entity add page. It is based on Paragraphs
EXPERIMENTAL widget.

The main use case is where we need to have a default content structure for a
given entity type.

The paragraph types set as default would be shown as "closed" on entity add
page by default, but for the case where given paragraph type has required
fields it will be opened.

Default Paragraphs allows other modules to modify the paragraph entities by
subscribing for an event called `DefaultParagraphsEvents::ADDED`. It is
triggered right before they are set as default values of the widget. It
provides the possibility to set default values to the paragraph entity's fields
if needed.

It is useful when we have multiple paragraph reference field.

## Requirements
* Paragraphs


### Project Information
* Drupal project: https://www.drupal.org/project/default_paragraphs
