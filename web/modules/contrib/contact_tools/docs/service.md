# Service

This module based on own service, that can done everything described below. The service
can help you when you work from PHP. To call service just use:

```php
$contact_tools = \Drupal::service('contact_tools');
```

After that you can call all methods.

<a href="getForm"></a>

### getForm() and getFormAjax()

Those two method generate `$form` render array with specified contact form and return it for your further needs.

#### Parameters

- `$contact_form_id = 'default_form'`: (optional) contact form bundle name which you want to load. If not passes, will be loaded default contact form which can be selected via contact admin settings.
- `$form_state_additions = []`: (optional) An associative array with values which will be passed to `$form_state` of form. You can acces them in `hook_form_alter()` lately via `$form_state->getStorage()`.

#### Example

```php
$contact_tools = \Drupal::service('contact_tools');

// Just loading default form.
$default_form = $contact_tools->getForm();

// Load feedback form with AJAX submit handler.
$feedback_ajax = $contact_tools->getFormAjax('feedback');

// Pass additional information to form. This value can be accesed during alter.
$default_form = $contact_tools->getForm('callback', ['page' => 'My page']);
```

<a href="createModalLink"></a>

### createModalLink() and createModalLinkAjax()

This methods generate '#link' which will load form in the modal on click. You can change modal settings and pass query parameters with link for future use in form alter.

#### Parameters

- `$link_title`: title of link. This variable is not translatable, if you need it, you must handle it by youself.
- `$contact_form`: the name of the contact which will be loaded in modal.
- `$link_options`: (optional) an array of options passed to link generation. For available options see `Url::fromUri()`. Here you can pass additional query parameters with link and link attributes such as class and data-dialog-option, which can be used to change jQuery ui dialog behavior. For more information about available dialo options see http://api.jqueryui.com/dialog/.
  ```php
  $link_options_defaults = [
    'attributes' => [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => [
        'width' => 'auto',
      ],
    ],
  ];
  ```

#### Examples

```php
$contact_tools = \Drupal::service('contact_tools');

// Link which open contact tools in modal without AJAX handler.
$feedback_in_modal = $contact_tools->createModalLinkAjax('Write to use!', 'feedback');

// Link which open contact form in modal with AJAX submit handler.
$callback_link = $contact_tools->createModalLinkAjax('Call me', 'callback');

// This link pass query parameters to controller, that can be used for your needs.
// Also set modal width to 300 and additional class to link 'request-support-button'.
// By pass nid in service, you can access it in hook_form_alter() hooks by
// \Drupal::request()->query->get('service') and do whatever you want with it. F.e.
// set this value and hide form field from user, that service name will be send with
// form, but user don't need to fill and see it.
$link_options = [
  'query' => [
    'service' => $node->id(),
  ],
  'attributes' => [
    // use-ajax class will be added anyway. You don't need to worry about it.
    'class' => ['request-support-button'],
    'data-dialog-options' => [
      'width' => 300,
    ]
  ],
];
$request_support = $contact_tools->createModalLinkAjax('Call me', 'callback', $link_options);
```
