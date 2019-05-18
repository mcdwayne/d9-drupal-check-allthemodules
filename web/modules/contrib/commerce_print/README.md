# Commerce Print

A small utility module to help users print Commerce Orders.

**"Print" action doesn't rely on any third party library**.

We have choosen to use browsers' built-in _Print to file_ functionality.

We think it's the most easy, efficient and certainly cross-platform way to give users the ability to print to PDF.

## Installation

Use [Composer](https://getcomposer.org/) to get Drupal + Commerce + Commerce Print.

```
composer require drupal/commerce_print
```

See the [install documentation](https://docs.drupalcommerce.org/commerce2/developer-guide/install-update/installation) for more details.

## Introduction

Commerce Print adds a few things for you:
* The **Print** display mode for Commerce Order types.
* The **Print** link which needs to be placed in the display mode.

Example:

![Commerce Order default display configuration](https://matthieuscarset.com/sites/default/files/2019-02/commerce_print_default_display.png)

## Theming

If you need to configure the **Print** display mode, go to _Admin > Store > Config > Order > Order Types > Default_.

Also, some default templates are provided inside `commerce_print/templates/`

Copy/paste them in your custom theme as needed for customization.

Example: **Custom page template to print to PDF**

![Custom template examples](https://matthieuscarset.com/sites/default/files/2019-02/commerce_print_template_examples.png)

```
{#
/**
 * @file
 * Theme override to display a single page to print an Order.
 *
 * @see template_preprocess_page()
 * @see page--user--orders--print.html.twig
 * @see page.html.twig
 */
#}

{{ attach_library('commerce_print/print') }}

<div class="layout-print">

  {% block print_before_content %}
    <div class="commerce-print-action">
      <a class="button button--primary" href="javascript:if(window.print)window.print()">{{ 'Print'|t }}</a>
    </div>
  {% endblock %}

  {% block content %}
    <main role="main">
        {{ page.content }}
    </main>
  {% endblock %}

  {% block print_after_content %}
    <div class="commerce-print-action">
      <a class="button button--primary" href="javascript:if(window.print)window.print()">{{ 'Print'|t }}</a>
    </div>
  {% endblock %}

</div>
```

## Related modules
* [entity_print](https://www.drupal.org/project/entity_print)

---

Questions? [Contact me](https://matthieuscarset.com/).
