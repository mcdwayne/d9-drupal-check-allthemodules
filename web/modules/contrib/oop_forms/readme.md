This module provides set of wrappers for building drupal forms in OOP way.
It takes the burden of writing FAPI arrays manually off the developers
and let them build those arrays with set of method calls.

The old way:
```PHP
$form['text_field'] = array(
  '#type' => 'textfield',
  '#title' => t('Old text field'),
  '#default_value' => 'This is exhausting',
  '#description' => t('Manually created text field using FAPI array.'),
);
```

The new OOP way:
```PHP
// Get OOP Forms service for easier use
/** @var \Drupal\oop_forms\Form\Builder $builder */
$builder = \Drupal::service('oop_forms.builder');

$form['text_field'] = $builder
  ->createTextField()
  ->setTitle(t('OOP text field'))
  ->setDefaultValue('This is fun!')
  ->setDescription(t('Text field generated with OOP forms service'))
  ->build()
;
```

This module is work in progress. To add support for more FAPI elements,
look for missing elements in `Drupal\Core\Render\Element` namespace
(its files are located at `core/lib/Drupal/Core/Render/Element`).
