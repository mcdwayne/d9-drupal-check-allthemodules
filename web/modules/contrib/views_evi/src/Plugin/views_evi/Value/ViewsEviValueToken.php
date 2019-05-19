<?php

namespace Drupal\views_evi\Plugin\views_evi\Value;

use Drupal\views_evi\ViewsEviHandlerTokenInterface;
use Drupal\views_evi\ViewsEviValueInterface;

/**
 * @ViewsEviValue(
 *   id = "token",
 *   title = "Value with token",
 * )
 */
class ViewsEviValueToken extends ViewsEviValueTokenBase implements ViewsEviHandlerTokenInterface, ViewsEviValueInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($settings, &$form) {

    $settings_form = parent::settingsForm($settings, $form);

    // Return our plugin settings form.
    $settings_form['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Filter value'),
      '#description' => t('You can use replacement tokens as listed below. This is known not to work for nested values like date and price field filters.'),
      '#default_value' => $settings['value'],
    );
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $filter_identifier = $this->getFilterWrapper()->getIdentifier();
    return array('value' => "[form:$filter_identifier]");
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $settings = $this->getFilterWrapper()->getPluginSettings('value');
    $value_with_tokens = $settings['value'];

    $token_replacements = $this->getTokenReplacements();
    $value = strtr($value_with_tokens, $token_replacements);
    $identifier = $this->getFilterWrapper()->getIdentifier();

    return $value !== '' ? array($identifier => $value) : array();
  }

}
