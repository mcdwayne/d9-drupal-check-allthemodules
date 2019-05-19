Selectize.js
------------

Installing
==========

From the Selectize.js GitHub, you will need to download it into /libraries/selectize:

- https://github.com/selectize/selectize.js

Using
=====

At the top of your file, include the Selectize element class:

use Drupal\selectize\Element\Selectize;

In your form(s) where you want to Selectize an input, you can do the following:

$form['example'] = array(
  '#type' => 'selectize',
  '#title' => t('Selectize'),
  '#settings' => Selectize::settings(),
  '#options' => array('' => 'Pick something or enter your own', 1 => 'ABC', 2 => 'DEF', 3 => 'GHI'),
);

This will provide a basic Selectized element.

You can also include your own settings overrides, like so:

$form['example'] = array(
  '#type' => 'selectize',
  '#title' => t('Selectize'),
  '#settings' => Selectize::settings(
    array(
      'maxItems' => 5,
      'plugins' => array('remove_button', 'drag_drop'),
    )
  ),
  '#options' => array('' => 'Pick something or enter your own', 1 => 'ABC', 2 => 'DEF', 3 => 'GHI'),
);

To see all the plugins and options available to you for overriding, see the plugin homepage at http://brianreavis.github.io/selectize.js/