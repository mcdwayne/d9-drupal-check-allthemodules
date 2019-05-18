<?php

namespace Drupal\affiliates_connect\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the end plugin.
 *
 * @Tamper(
 *   id = "end",
 *   label = @Translation("End"),
 *   description = @Translation("Return elements from last of the break up sequenced data"),
 *   category = "Affiliates Connect"
 * )
 */
class End extends TamperBase {

  const SETTING_SEPARATOR = 'separator';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_SEPARATOR] = ',';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_SEPARATOR] = [
      '#type' => 'textfield',
      '#title' => $this->t('String separator'),
      '#default_value' => $this->getSetting(self::SETTING_SEPARATOR),
      '#description' => $this->t('This will break up sequenced data into an
      array and return the last element. For example, "a, b, c" would get broken up into the array(\'a\',
      \'b\', \'c\') and \'c\' will be the output . A space can be represented by %s, tabs by %t, and newlines
      by %n.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_SEPARATOR => $form_state->getValue(self::SETTING_SEPARATOR),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      return;
    }
    $separator = str_replace(['%s', '%t', '%n'], [' ', "\t", "\n"], $this->getSetting(self::SETTING_SEPARATOR));
    return end(explode($separator, $data));
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
