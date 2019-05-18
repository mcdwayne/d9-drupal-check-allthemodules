<?php

namespace Drupal\multi_node_add\Form;

use Drupal\Core\Form\FormBase;
use Drupal\node\NodeTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form to launch Multi Node Add process.
 */
class MultiNodeAddForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'multi-node-add';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   * @param Drupal\node\NodeTypeInterface $node_type
   *   Selected node type.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeTypeInterface $node_type = NULL) {
    global $base_path;
    $form['#attached']['drupalSettings']['multiNodeAdd'] = [
      'callback' => $base_path . Url::fromRoute('multi_node_add.frame', ['node_type' => $node_type->getOriginalId(), 'fields' => '#fields'], ['absolute' => TRUE])->getInternalPath(),
    ];

    $prefilled = FALSE;
    if (isset($_GET['fields']) && isset($_GET['num'])) {
      $form['#attached']['drupalSettings']['multiNodeAdd'] = [
        'multiNodeAddPreload' => [
          'fields' => explode(',', $_GET['fields']),
          'num' => $_GET['num'],
        ],
      ];
      $prefilled = TRUE;
    }

    if (!$prefilled) {

      $fields = \Drupal::entityManager()->getFieldDefinitions('node', $node_type->get('type'));
      $req_val = [];
      $field_req = [];
      $field_names = [];

      foreach ($fields as $field_name => $entry) {
        if (!$entry->isReadOnly() && !$entry->isComputed()) {
          if ($entry->isRequired()) {
            $field_req[$field_name] = $entry->getLabel();
            $req_val[$field_name] = $field_name;
          }
          else {
            $field_names[$field_name] = $entry->getLabel();
          }
        }
      }

      $form['#attached']['library'][] = 'multi_node_add/multi_node_add';
      $form['hint']['#markup'] = '<div class="warning messages js">' . t('Multi Node Add requires Javascript to provide the needed functionality') . '</div>';
      $form['info']['#value'] = t('Current content-type: %type', ['%type' => $node_type->label()]);
      if (!empty($field_req)) {
        $form['fields_req'] = [
          '#type' => 'checkboxes',
          '#options' => $field_req,
          '#default_value' => $req_val,
          '#title' => t('Mandatory fields'),
          '#attributes' => ['class' => ['multi-node-add']],
          '#disabled' => TRUE,
        ];
      }
      if (!empty($field_names)) {
        $form['fields_to_utilize'] = [
          '#type' => 'checkboxes',
          '#options' => $field_names,
          '#title' => t('Fields to manage'),
          '#attributes' => ['class' => ['multi-node-add']],
          '#description' => t('Choose those fields that you would like to edit on the new nodes'),
        ];
      }

      // If there are no available fields, we should not offer a form.
      if (empty($field_names) && empty($field_req)) {
        drupal_set_message(t('Unable to generate multiple nodes for this content type (failed to detect usable fields).'), 'warning');
        return $form;
      }

      $form['number'] = [
        '#type' => 'textfield',
        '#default_value' => 2,
        '#size' => 2,
        '#required' => TRUE,
        '#title' => t('Number of rows'),
      ];
      $form['show'] = [
        '#type' => 'button',
        '#value' => t('Show'),
      ];
      $form['shortcut'] = [
        '#type' => 'button',
        '#value' => t('Get shortcut URL'),
      ];
    }

    $common_attr = [
      '#attributes' => [
        'class' => [
          'second-step',
        ],
      ],
    ];
    $form['addmore'] = [
      '#type' => 'button',
      '#value' => t('Add 2 more nodes'),
    ] + $common_attr;
    $form['create'] = [
      '#type' => 'button',
      '#value' => t('Create all nodes'),
    ] + $common_attr;
    $form['prepopulate'] = [
      '#type' => 'button',
      '#value' => t('Prepopulate based on first form'),
    ] + $common_attr;
    $form['placeholder']['#markup'] = '<div id="multi_node_add_frames"></div>';

    return $form;
  }

  /**
   * Form submission handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form never gets submitted, really.
    // Everything is handled by Javascript.
  }

}
