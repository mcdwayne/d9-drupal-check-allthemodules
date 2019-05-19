Simple Modal Entity Form 8.x-1.x
---------------

### About this Module

The Simple Modal Entity Form module provides a way to edit entities in a modal.

Currently supported are:
- Modal links for editing, creating and deleting content entities.
- Possibility to set the **display mode** used to render the form.
- An 'edit in modal' link for Views.
- A 'delete in modal' link for Views.
- A way to add 'modal' action links to pages.

### Instructions

The operation links for editing and deleting entities are automatically added to the Views Interface.

To add an action for a 'modal' entity form, add the following to your module.action.yml file: 

```
your_module.your_link:
  route_name: modal_entity_form.add
  route_parameters:
    entity_type: foo
    bundle: bar
    form_mode: baz
  appears_on:
      - some.router.link
  title: 'Add entity'
  class: 'Drupal\modal_entity_form\Menu\LocalActionWithModal'
```

Checkout the modal_entity_form.routing.yml for the other routes available.

If the entity type doesn't support bundles, just repeat the entity type.

### Wish list
- Add tests.
- Provide a widget for the entity reference field type.

### Disclaimer
This module is in a very early beta stage and not suitable for production yet. Please leave any issues in the issue queue.

### Similar Modules

- [Modal Entity Form (Sandbox)](https://www.drupal.org/sandbox/michaelpetri/2827680)
