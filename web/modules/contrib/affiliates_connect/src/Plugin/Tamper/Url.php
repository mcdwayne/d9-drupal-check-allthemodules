<?php

namespace Drupal\affiliates_connect\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Url plugin.
 *
 * @Tamper(
 *   id = "url",
 *   label = @Translation("Url"),
 *   description = @Translation("Return absolute url if not defined"),
 *   category = "Affiliates Connect"
 * )
 */
class Url extends TamperBase {

  const SETTING_BASE = 'base_url';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_BASE] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_BASE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base url'),
      '#default_value' => $this->getSetting(self::SETTING_BASE),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_BASE => $form_state->getValue(self::SETTING_BASE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      return;
    }
    $base_url = $this->getSetting(self::SETTING_BASE);
    // Validate url
    if (!filter_var($data, FILTER_VALIDATE_URL) === false) {
         return $data;
    }
    return $base_url . $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
