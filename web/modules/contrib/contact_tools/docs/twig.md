# Twig

Twig is awsome and used alot in Drupal theming. Module is providing Twig functions for contact forms aswell!

### Examples

```twig
{# Just contact form. #}
{{ contact_form('feedback') }}

{# Contact form with AJAX. #}
{{ contact_form_ajax('feedback') }}

{# Contact form with data. #}
{{ contact_form('order', { product: node.label() });
{{ contact_form_ajax('order', { product: node.label() });

{# Modal link with AJAX form. #}
{{ contact_modal_ajax('Write to us!', 'feedback') }}

{# Create modal link and pass some data with it #}
{% set link_options = {
  query: {
    service: node.id()
  },
  attributes: {
    class: ['my-awesome-link'],
    'data-dialog-options': {
      width: '600'
    }
  }
} %}
{{ contact_modal_ajax('Write to us!', 'feedback', link_options) }}

{# Or directly in function #}
{{ contact_modal_ajax('Write to us!', 'feedback', {query: {service: node.id()}}) }}
```