<?php

namespace Drupal\ofed_switcher\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OfedSwitcherConfiguration.
 *
 * @package Drupal\ofed_switcher\Controller
 */
class OfedSwitcherConfiguration extends ConfigFormBase {

  /**
   * The configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['ofed_switcher.configuration'];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'ofed_switcher_configuration';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $this->config = $this->config('ofed_switcher.configuration');

    $form = [
      'backend' => [
        '#type' => 'textfield',
        '#title' => 'Go to CMS path',
        '#default_value' => $this->config->get('backend'),
        '#field_prefix' => $base_url,
      ],
      'frontend' => [
        '#type' => 'textfield',
        '#title' => 'Go to frontend',
        '#default_value' => $this->config->get('frontend'),
        '#field_prefix' => $base_url,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('backend'))) {
      $form_state->setErrorByName('backend', $this->t('The backend path cannot be empty.'));
    }
    if (empty($form_state->getValue('frontend'))) {
      $form_state->setErrorByName('frontend', $this->t('The frontend path cannot be empty.'));
    }
    parent::validateForm($form, $form_state);
  }


  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ofed_switcher.configuration');
    $config->set('backend', $form_state->getValue('backend'));
    $config->set('frontend', $form_state->getValue('frontend'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}