<?php

declare(strict_types = 1);

namespace Drupal\views_ef_fieldset\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DefaultDisplayExtender;
use Drupal\views_ef_fieldset\ViewsEFFieldsetData;

/**
 * Views EF Fieldset display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_ef_fieldset",
 *   title = @Translation("Views EF Fieldset display extender"),
 *   help = @Translation("Views EF Fieldset settings for this view."),
 *   no_ui = FALSE
 * )
 */
class ViewsEFFieldset extends DefaultDisplayExtender {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state->get('section') !== 'exposed_form_options') {
      return [];
    }

    $options = $this->options['views_ef_fieldset'];
    $defaults = $this->getPluginDefinition();

    $form['views_ef_fieldset'] = [
      '#tree' => TRUE,
    ];

    $form['views_ef_fieldset']['enabled'] = [
      '#type' => 'checkbox',
      '#default_value' => isset($options['enabled']) ?
      $options['enabled'] :
      $defaults['views_ef_fieldset']['enabled']['default'],
      '#title' => t('Enable fieldset around exposed forms ?'),
    ];

    $form['views_ef_fieldset']['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Exposed form fieldset options'),
      '#states' => [
        'visible' => [
          ':input[name="views_ef_fieldset[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $exposed_fields = $this->view->getHandlers('filter');
    foreach ($exposed_fields as $exposed_field_index => $exposed_field) {
      $exposed_fields[$exposed_field_index]['handler_type'] = 'filter';
      if ((bool) $exposed_field['exposed'] !== TRUE) {
        unset($exposed_fields[$exposed_field_index]);
      }
    }

    $sort_fields = $this->view->getHandlers('sort');
    foreach ($sort_fields as $sort_fields_index => $sort_field) {
      $sort_fields[$sort_fields_index]['handler_type'] = 'sort';
      if ((bool) $sort_field['exposed'] !== TRUE) {
        unset($sort_fields[$sort_fields_index]);
      }
    }

    if (!empty($sort_fields)) {
      $sort_by = [];
      $sort_by['handler_type'] = 'sort';
      $sort_by['id'] = 'sort_by';
      $sort_by['expose'] = [
        'label' => 'Sort by',
      ];

      $exposed_fields[] = $sort_by;
    }

    if ($form['exposed_form_options']['expose_sort_order']['#default_value'] === 1 && count($sort_fields)) {
      $sort_order = [];
      $sort_order['handler_type'] = 'sort';
      $sort_order['id'] = 'sort_order';
      $sort_order['expose'] = [
        'label' => 'Sort order',
      ];
      $exposed_fields[] = $sort_order;
    }

    $submit_button = [];
    $submit_button['handler_type'] = 'buttons';
    $submit_button['id'] = 'submit';
    $submit_button['expose'] = [
      'label' => 'Submit button',
    ];
    $exposed_fields[] = $submit_button;

    if ($form['exposed_form_options']['reset_button']['#default_value'] === 1) {
      $reset_button = [];
      $reset_button['handler_type'] = 'buttons';
      $reset_button['id'] = 'reset';
      $reset_button['expose'] = [
        'label' => 'Reset button',
      ];
      $exposed_fields[] = $reset_button;
    }

    foreach (array_values($exposed_fields) as $exposed_field_index => $exposed_field) {
      $container = [];
      $container['handler_type'] = 'container';
      $container['type'] = 'container';
      $container['container_type'] = 'details';
      $container['weight'] = $exposed_field_index;
      $container['expose'] = [
        'label' => 'Container ' . $exposed_field_index,
      ];
      $container['id'] = 'container-' . $exposed_field_index;
      $exposed_fields[] = $container;
    }

    $data = [
      [
        'id' => 'root',
        'type' => 'container',
        'weight' => 0,
        'pid' => '',
        'label' => 'Root',
        'title' => isset($options['options']['sort']['root']['title']) ?
        $options['options']['sort']['root']['title'] : t('Filters'),
        'description' => $options['options']['sort']['root']['description'],
        'open' => isset($options['options']['sort']['root']['open']) ?
        (bool) $options['options']['sort']['root']['open'] : TRUE,
        'container_type' => isset($options['options']['sort']['root']['container_type']) ?
        $options['options']['sort']['root']['container_type'] : 'details',
      ],
    ];

    foreach ($exposed_fields as $index => $field) {
      $field_options = $options['options']['sort'][$field['id']];
      $label = ($field['expose']['label']) ? $field['expose']['label'] : $field['id'];

      $data[] = [
        'id' => $field['id'],
        'weight' => isset($field_options['weight']) ? $field_options['weight'] : (isset($field->weight) ? $field->weight : $index - count($exposed_fields)),
        'pid' => empty($field_options['pid']) ? 'root' : $field_options['pid'],
        'label' => $label,
        'title' => isset($field_options['title']) ? $field_options['title'] : $label,
        'description' => isset($field_options['description']) ? $field_options['description'] : '',
        'open' => isset($field_options['open']) ? (bool) $field_options['open'] : FALSE,
        'type' => $field['handler_type'],
        'container_type' => isset($field_options['container_type']) ? $field_options['container_type'] : 'details',
      ];
    }

    $viewsEFFieldsetData = new ViewsEFFieldsetData($data);

    $table = [
      '#type' => 'table',
      '#header' => [
        t('Label'),
        t('Type'),
        t('Title'),
        t('Description'),
        t('Open'),
        t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'item-pid',
          'subgroup' => 'item-pid',
          'source' => 'item-id',
          'hidden' => FALSE,
        ],
        [
          'action' => 'depth',
          'relationship' => 'group',
          'group' => 'item-depth',
          'hidden' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'item-weight',
        ],
      ],
    ];

    foreach ($viewsEFFieldsetData->buildFlat() as $item) {
      $item = $item['item'];

      $indentation = [];
      if (isset($item['depth']) && $item['depth'] > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $item['depth'],
        ];
      }

      $title = $item['title'];
      if ($item['type'] === 'container') {
        if ($item['id'] === 'root') {
          $title = '<em>' . $title . '</em>';
        }
        $title = '<strong>' . $title . '</strong>';
      }

      $table[$item['id']] = [
        '#item' => $item,
        'item' => [
          '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
          '#markup' => $title,
          '#wrapper_attributes' => [
            'colspan' => ($item['type'] === 'container') ? '' : ['colspan' => 5],
          ],
          'id' => [
            '#type' => 'hidden',
            '#default_value' => $item['id'],
            '#size' => 4,
            '#attributes' => [
              'class' => ['item-id'],
            ],
          ],
          'pid' => [
            '#type' => 'hidden',
            '#default_value' => $item['pid'],
            '#size' => 4,
            '#attributes' => [
              'class' => ['item-pid'],
            ],
          ],
          'depth' => [
            '#type' => 'hidden',
            '#default_value' => $item['depth'],
            '#attributes' => [
              'class' => ['item-depth'],
            ],
          ],
          'type' => [
            '#type' => 'hidden',
            '#default_value' => $item['type'],
          ],
        ],
      ];

      if ($item['type'] === 'container') {
        $table[$item['id']] += [
          'container_type' => [
            '#type' => 'select',
            '#default_value' => $item['container_type'],
            '#options' => [
              'container' => 'Container',
              'details' => 'Fieldset',
              'vertical_tabs' => 'Vertical tabs',
            ],
          ],
          'title' => [
            '#type' => 'textfield',
            '#size' => 15,
            '#default_value' => $item['title'],
          ],
          'description' => [
            '#type' => 'textfield',
            '#size' => 15,
            '#default_value' => $item['description'],
          ],
          'open' => [
            '#type' => 'checkbox',
            '#default_value' => $item['open'],
          ],
        ];
      }

      $table[$item['id']] += [
        'weight' => [
          '#item' => $item,
          '#type' => 'weight',
          '#title' => $item['title'],
          '#delta' => count($data),
          '#title_display' => 'invisible',
          '#default_value' => $item['weight'],
          '#attributes' => [
            'class' => ['item-weight'],
          ],
        ],
      ];

      if ($item['id'] !== 'root') {
        $table[$item['id']]['#attributes']['class'][] = 'draggable';
      }
    }

    $form['views_ef_fieldset']['options']['sort'] = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $options = parent::defineOptions();

    $options['views_ef_fieldset'] = [
      'enabled' => ['default' => FALSE, 'bool' => TRUE],
      'options' => [],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Only process options if this is an unrelated form.
    if ($form_state->get('section') === 'exposed_form_options') {
      $views_ef_fieldset = $form_state->getValue('views_ef_fieldset');
      foreach ($views_ef_fieldset['options']['sort'] as $key => $data) {
        $data += $data['item'];
        unset($data['item']);
        $views_ef_fieldset['options']['sort'][$key] = $data;
      }

      $this->options['views_ef_fieldset'] = $views_ef_fieldset;
    }
    parent::submitOptionsForm($form, $form_state);
  }

}
