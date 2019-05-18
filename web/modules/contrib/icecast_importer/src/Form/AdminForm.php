<?php

namespace Drupal\icecast_importer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'icecast_importer_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('icecast_importer.settings');

    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['node_type'] = [
      '#type' => 'select',
      '#title' => $this->t('icecast node type'),
      '#description' => $this->t('The node type of icecast.'),
      '#options' => $options,
      '#default_value' => $config->get('node_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $node_type = $form_state->getValue('node_type');
    $node = Node::create(['type' => $node_type]);
    if (!$node->hasField('field_info') || !$node->hasField('field_track')) {
      $form_state->setErrorByName('node_type', $this->t('The node type you selected lack of some fields(info or track).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('icecast_importer.settings');
    $config->set('node_type', $form_state->getValue('node_type'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'icecast_importer.settings',
    ];
  }

}
