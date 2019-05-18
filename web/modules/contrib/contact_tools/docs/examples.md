# Examples

## Animated SVG after AJAX successful submission

For example, you want to do something like [this](https://codepen.io/seansean11/pen/fBjIi) after form successfully submitted. That's easy!

1. Save this svg as a file, for easier access and distribution. Name it, f.e. check-icon.svg.
2. In your custom code create `hook_contact_tools_ajax_response_alter()`. This way we will attach this behaviour to all AJAX submissions.
    
```php
/**
 * Implements hook_contact_tools_ajax_response_alter().
 *
 * Allows modules to alter AJAX response handled by the module. You can fully
 * alter, remove and add new commands to response.
 */
function MYMODULE_contact_tools_ajax_response_alter(AjaxResponse &$ajax_response, $form, Drupal\Core\Form\FormStateInterface $form_state) {
  // Only when form submitted without errors.
  if ($form_state->isExecuted()) {
    $thanks = [
      '#type' => 'inline_template',
      '#template' => '<div id="form-submitted-message"><div class="icon">{{ icon|raw }}</div>{{ message }}</div>',
      '#context' => [
        'message' => 'Thank you for your submission!',
        'icon' => file_get_contents(drupal_get_path('theme', 'THEME') . '/images/check-icon.svg'),
      ],
    ];
    $ajax_response->addCommand(new ReplaceCommand('#contact-form-' . $form['#build_id'], $thanks));
  }
}
```

3. Copy-paste css for animation from codepen. Or create your one.

```scss
@keyframes check-icon {
  0% {
    stroke-dashoffset: 745.74853515625;
  }
  100% {
    stroke-dashoffset: 0;
  }
}

#form-submitted-message {
  text-align: center;
  font-size: 18px;
  font-weight: bold;
  color: green;
  padding: 32px 0;

  .icon {
    margin-bottom: 16px;

    svg {
      width: 100px;

      .checkmark {
        width: 100px;
        stroke: green;
        stroke-dashoffset: 745.74853515625;
        stroke-dasharray: 745.74853515625;
        animation: check-icon 2s ease-out forwards;
      }
    }
  }
}
```
    
4. Clear the cache and see the result!

[![Result](https://media.giphy.com/media/7IW6vwFrzxvR2A2YmB/giphy.gif)](https://giphy.com/gifs/7IW6vwFrzxvR2A2YmB/html5)

## Pass some data to the form.

1. E.g. call form from node template.

```twig
{{ contact_form_ajax('order', { product: node.label() }) }}
```

2. Alter that form and set this value to field and hide it from user.

```php
/**
 * Implements hook_form_FORM_ID_alter().
 */
function MYMODULE_form_contact_message_order_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $storage = $form_state->getStorage();
  $hidden_text = '';
  if (!empty($storage['product'])) {
    $hidden_text .= 'Product: ' . $product_name . PHP_EOL;
    $form['field_hidden_body']['widget'][0]['value']['#default_value'] = $hidden_text;
  }
  hide($form['field_hidden_body']);
}
```