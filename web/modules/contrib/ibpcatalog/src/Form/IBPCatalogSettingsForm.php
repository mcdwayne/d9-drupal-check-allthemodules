<?php

/**
 * @file
 * Contains \Drupal\ibpcatalog\Form\IbpcatalogController.
 */

namespace Drupal\ibpcatalog\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure book settings for this site.
 */
class IBPCatalogSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ibpcatalog_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('ibpcatalog.settings');
    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Catalog Key'),
      '#default_value' => $config->get('key'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('ibpcatalog.settings')
    // Remove unchecked types.
      ->set('key', $form_state['values']['key'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
