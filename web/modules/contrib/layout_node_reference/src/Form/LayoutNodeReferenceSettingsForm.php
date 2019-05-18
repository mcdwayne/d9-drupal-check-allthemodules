<?php

namespace Drupal\layout_node_reference\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Class LayoutNodeReferenceSettingsForm.
 *
 * @package Drupal\layout_node_reference\Form
 */
class LayoutNodeReferenceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_node_reference.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('layout_node_reference.settings');
    $form['settings_description'] = [
      '#markup' => '<p>' . t('Please configure the list of node types you want to embed in layouts. Until any of the items are checked - no content can be referenced.') . '</p>',
    ];

    $form['reference_settings'] = [
      '#type' => 'details',
      '#title' => t('Node types'),
    ];

    $node_types = NodeType::loadMultiple();
    $node_type_options = [];
    foreach ($node_types as $node_type) {
      $node_type_options[$node_type->id()] = $node_type->label();
    }
    $form['reference_settings']['layout_allow_embed'] = [
      '#title' => $this->t('Allow embedding'),
      '#type' => "select",
      '#options' => $node_type_options,
      '#default_value' => $config->get('layout_allow_embed'),
      '#description' => t('Select a set of node types that can be embedded in layout blocks'),
      '#multiple' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

    $this->config('layout_node_reference.settings')
      ->set('layout_allow_embed', $form_state->getValue('layout_allow_embed'))
      ->save();

  }

}
