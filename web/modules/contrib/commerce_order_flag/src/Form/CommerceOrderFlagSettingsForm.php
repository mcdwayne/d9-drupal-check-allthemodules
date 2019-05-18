<?php

namespace Drupal\commerce_order_flag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure commerce_order_flag settings for this site.
 */
class CommerceOrderFlagSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'commerce_order_flag_admin_settings';
  }

  /**
   * {@inheritdoc}
   */

  protected function getEditableConfigNames() {
    return [
      'commerce_order_flag.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_order_flag.settings');

    $flag_label = $config->get('flag_label');
    $flag_bulk_set = $config->get('flag_bulk_set');
    $flag_bulk_unset = $config->get('flag_bulk_unset');

    $form['flag_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag label'),
      '#default_value' => $flag_label,
    ];
    $form['flag_bulk_set'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bulk name (mark TRUE)'),
      '#default_value' => $flag_bulk_set,
    ];
    $form['flag_bulk_unset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bulk name (mark FALSE)'),
      '#default_value' => $flag_bulk_unset,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('commerce_order_flag.settings')
      ->set('flag_label', $form_state->getValue('flag_label'))
      ->set('flag_bulk_set', $form_state->getValue('flag_bulk_set'))
      ->set('flag_bulk_unset', $form_state->getValue('flag_bulk_unset'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
