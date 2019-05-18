<?php

namespace Drupal\reference_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 *
 * @package Drupal\reference_access\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'reference_access.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ref_access_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('reference_access.config');


    $form['check_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types to restrict'),
      '#description' => $this->t('Content types that should be checked for reference access. Nodes that are not chosen will be ignored by this module (allowed access), while nodes ticked will have to be referenced in some way depending on the rules chosen below.'),
      '#options' => [],
      '#default_value' => $config->get('check_content_types'),
    ];

    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')->loadMultiple();
    // kint($contentTypes, '$contentTypes');
    foreach ($contentTypes as $name => $contentType) {
      $form['check_content_types']['#options'][$name] = $contentType->label();
    }


    $form['check_direct_ref_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check nodes directly referenced by users'),
      '#description' => $this->t('For example: user -> node.'),
      '#default_value' => $config->get('check_direct_ref_nodes'),
    ];
    $form['check_indirect_ref_nodes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check nodes referenced by nodes that are referenced by users'),
      '#description' => $this->t('For example: user -> node -> node.'),
      '#default_value' => $config->get('check_indirect_ref_nodes'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('reference_access.config')
      ->set('check_content_types', $form_state->getValue('check_content_types'))
      ->set('check_direct_ref_nodes', $form_state->getValue('check_direct_ref_nodes'))
      ->set('check_indirect_ref_nodes', $form_state->getValue('check_indirect_ref_nodes'))
      ->save();
  }

}
