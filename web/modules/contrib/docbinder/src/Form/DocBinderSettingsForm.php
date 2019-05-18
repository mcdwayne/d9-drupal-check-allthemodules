<?php

namespace Drupal\docbinder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure example settings for this site.
 */
class DocBinderSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'docbinder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'docbinder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('docbinder.settings');

    $node_types = NodeType::loadMultiple();
    $bundle_options = [];
    foreach ($node_types as $node_type_id => $node_type) {
      /** @var \Drupal\node\NodeTypeInterface $node_type */
      $bundle_options[$node_type_id] = $node_type->label();
    }

    $form['collection_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collection name'),
      '#default_value' => $config->get('collection.name'),
      '#description' => $this->t('This will only affect the download page. The block label needs to be changed through the block settings.'),
    ];

    $form['collection_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types restriction'),
      '#options' => $bundle_options,
      '#default_value' => $config->get('collection.bundles'),
      '#description' => $this->t('By default, DocBinder works on all file links in any content type. Check all content types for which DocBinder shall be disabled.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $config = $this->config('docbinder.settings');
      // Set the submitted configuration setting
    $config->set('collection.name', $form_state->getValue('collection_name'));
    $config->set('collection.bundles', $form_state->getValue('collection_bundles'));
      // You can set multiple configurations at once by making
      // multiple calls to set()
      // ->set('other', $form_state->getValue('other'))
    $config->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }
}
