<?php

namespace Drupal\simple_adsense\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimpleAdSenseForm.
 *
 * @package Drupal\simple_adsense\Form
 */
class SimpleAdSenseForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_adsense.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_adsense_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_adsense.settings');
    $form['publisher_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Simple AdSense Publisher ID'),
      '#description' => $this->t('Google AdSense Publisher Id. eg: pub-9513614146655499'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('publisher_id') ? $config->get('publisher_id') : 'pub-9513614146655499',
    );
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

    $this->config('simple_adsense.settings')
      ->set('publisher_id', $form_state->getValue('publisher_id'))
      ->save();
  }

}
