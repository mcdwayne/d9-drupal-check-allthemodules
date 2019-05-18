<?php

namespace Drupal\node_clone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class NodeCloneSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_clone_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('node_clone.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['node_clone.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => t('General settings'),
    ];
    $form['basic']['clone_method'] = [
      '#type' => 'radios',
      '#title' => t('Method to use when cloning a node'),
      '#options' => [
        'prepopulate' => t('Pre-populate the node form fields'),
        'save-edit' => t('Save as a new node then edit'),
      ],
      '#default_value' => \Drupal::config('node_clone.settings')->get('node_clone_method'),
    ];
    $form['basic']['clone_nodes_without_confirm'] = [
      '#type' => 'radios',
      '#title' => t('Confirmation mode when using the "Save as a new node then edit" method'),
      '#default_value' => (int) \Drupal::config('node_clone.settings')->get('node_clone_nodes_without_confirm'),
      '#options' => [
        t('Require confirmation (recommended)'),
        t('Bypass confirmation'),
      ],
      '#description' => t('A new node may be saved immediately upon clicking the "clone" link when viewing a node, bypassing the normal confirmation form.'),
      '#states' => [
        // Only show this field when the clone method is save-edit.
        'visible' => [
          ':input[name="clone_method"]' => [
            'value' => 'save-edit'
            ]
          ]
        ],
    ];
    // @FIXME
    // // @FIXME
    // // This looks like another module's variable. You'll need to rewrite this call
    // // to ensure that it uses the correct configuration object.
    // $form['basic']['clone_menu_links'] = array(
    //     '#type' => 'radios',
    //     '#title' => t('Clone menu links'),
    //     '#options' => array(0 => t('No'), 1 => t('Yes')),
    //     '#default_value' => (int) variable_get('clone_menu_links', 0),
    //     '#description' => t('Should any menu link for a node also be cloned?'),
    //   );

    $form['basic']['clone_use_node_type_name'] = [
      '#type' => 'checkbox',
      '#title' => t('Use node type name in clone link'),
      '#default_value' => (int) \Drupal::config('node_clone.settings')->get('node_clone_use_node_type_name'),
      '#description' => t('If checked, the link to clone the node will contain the node type name, for example, "Clone this article", otherwise it will read "Clone content".'),
    ];

    $form['publishing'] = [
      '#type' => 'fieldset',
      '#title' => t('Should the publishing options ( e.g. published, promoted, etc) be reset to the defaults?'),
    ];
    $types = node_type_get_names();

    foreach ($types as $type => $name) {
      // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $form['publishing']['clone_reset_' . $type] = array(
//       '#type' => 'checkbox',
//       '#title' => t('@s: reset publishing options when cloned', array('@s' => $name)),
//       '#default_value' => variable_get('node_clone_reset_' . $type, FALSE),
//     );

    }

    // Need the variable default key to be something that's never a valid node type.
    $form['omit'] = [
      '#type' => 'fieldset',
      '#title' => t('Content types that are not to be cloned - omitted due to incompatibility'),
    ];
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/node_clone.settings.yml and config/schema/node_clone.schema.yml.
    $form['omit']['clone_omitted'] = [
      '#type' => 'checkboxes',
      '#title' => t('Omitted content types'),
      '#default_value' => \Drupal::config('node_clone.settings')->get('node_clone_omitted'),
      '#options' => $types,
      '#description' => t('Select any node types which should <em>never</em> be cloned. In other words, all node types where cloning will fail.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
