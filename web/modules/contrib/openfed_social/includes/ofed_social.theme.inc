<?php
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Returns HTML to create the sortable multiselect table.
 *
 * @param $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function template_preprocess_ofed_social_admin_form(&$variables) {
  $form =& $variables['form'];
  $variables['social_networks'] = [];
  $header = [
    t('Network'),
    t('Enabled'),
    t('Weight'),
  ];
  $table = [
    '#type' => 'table',
    '#header' => $header,
    '#attributes' => ['id' => 'social-networks-order'],
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'social-networks-order-weight',
      ],
    ],
  ];

  foreach ($form['name'] as $key => $element) {
    // Do not take form control structures.
    if (is_array($element) && Element::child($key)) {
      // Add invisible labels for the checkboxes and radio buttons in the table
      // for accessibility. These changes are only required and valid when the
      // form is themed as a table, so it would be wrong to perform them in the
      // form constructor.
      $table[$key]['#attributes']['class'][] = 'draggable';
      $table[$key]['name'] = $element;
      $table[$key]['enabled'] = $form['enabled'][$key];

      $title = \Drupal::service("renderer")->render($element);
      $table[$key]['enabled']['#title'] = t('Enable @title', ['@title' => $title]);
      $table[$key]['enabled']['#title_display'] = 'invisible';

      $table[$key]['weight'] = $form['weight'][$key];

      // Unset to prevent rendering along with children.
      unset($form['name'][$key]);
      unset($form['enabled'][$key]);
      unset($form['weight'][$key]);
    }
  }

  $variables['social_networks'] = [
    'title' => t('Openfed Social Networks Selection'),
    'table' => $table,
    'attributes' => new Attribute(),
  ];
}
