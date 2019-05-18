<?php

namespace Drupal\czater\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CzaterForm.
 *
 * @package Drupal\czater\Form
 */
class CzaterForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'czater.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('czater.settings');

    $form['czater_kod'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Wklej swój kod'),
      '#default_value' => $config->get('czater_kod'),
    ];

    return parent::buildForm($form, $form_state);
  }

   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'czater_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('czater.settings')
      ->set('czater_kod', $form_state->getValue('czater_kod'))
      ->save();
  }

}
