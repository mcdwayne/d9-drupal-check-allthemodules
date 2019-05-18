<?php

/*
 * @file
 * Contains Drupal\node_display_admin_title\Form\NodeDisplayTitleAdminForm.
 */

namespace Drupal\node_display_title\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NodeDisplayTitleAdminForm extends FormBase {
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'node_display_title_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get list of node types and whether they use admin titles.
    /*
    $node_types = _node_display_title_get_node_types_list();
    $settings = variable_get('node_display_title_settings', array());
     */
    $checkbox_options = array();
    $default_values = array();

    $form[] = array(
      '#markup' => '<p>' . t('Set which node types should use admin titles.') . '</p>',
    );

//    foreach ($node_types as $type) {
//      $checkbox_options[] = $type;
//      if (in_array($type, $settings)) {
//        $default_values[] = $type;
//      }
//    }

//    $form['node_types'] = array(
//      '#type' => 'checkboxes',
//      '#title' => t('Select node types'),
//      '#options' => drupal_map_assoc($checkbox_options),
//      '#default_value' => $default_values,
//    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('save'),
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Form submitted'));
  }
}
