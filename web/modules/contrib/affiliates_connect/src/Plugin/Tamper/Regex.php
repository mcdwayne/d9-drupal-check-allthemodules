<?php

namespace Drupal\affiliates_connect\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Regex plugin.
 *
 * @Tamper(
 *   id = "regex",
 *   label = @Translation("Regex"),
 *   description = @Translation("Use Regular Expressions to extract data"),
 *   category = "Affiliates Connect"
 * )
 */
class Regex extends TamperBase {

  const SETTING_REGEX = 'regex';
  const SETTING_INDEX = 'index';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_REGEX] = '';
    $config[self::SETTING_INDEX] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_REGEX] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter Regex Expression'),
      '#default_value' => $this->getSetting(self::SETTING_REGEX),
      '#required' => TRUE,
    ];

    $form[self::SETTING_INDEX] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Index'),
      '#default_value' => $this->getSetting(self::SETTING_INDEX),
      '#description' => $this->t('If checked, It will return the element at index 1 if present, else return the 0th index element'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_REGEX => $form_state->getValue(self::SETTING_REGEX),
      self::SETTING_INDEX => $form_state->getValue(self::SETTING_INDEX),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      return '';
    }
    $regex = $this->getSetting(self::SETTING_REGEX);
    $index = $this->getSetting(self::SETTING_INDEX);
    preg_match_all($regex, $data, $output);
    if ($index && count($output) > 1) {
      return $output[1];
    }
    return $output[0];
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
