<?php

namespace Drupal\views_evi\Plugin\views_evi\Visibility;

use Drupal\views_evi\ViewsEviHandlerTokenInterface;
use Drupal\views_evi\ViewsEviVisibilityInterface;

/**
 * @ViewsEviVisibility(
 *   id = "fallback",
 *   title = "Visible if no value",
 * )
 */
class ViewsEviVisibilityFallback extends ViewsEviVisibilityTokenBase implements ViewsEviHandlerTokenInterface, ViewsEviVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($settings, &$form) {

    $settings_form = parent::settingsForm($settings, $form);

    // Return our plugin settings form.
    $settings_form['value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Visibility inhibiting value'),
      '#description' => $this->t('Form element will be visible if result value is empty. You can use replacement tokens as listed below.'),
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
  public function getVisibility(&$form){
    $settings = $this->getFilterWrapper()->getPluginSettings('visibility');
    $value_with_tokens = $settings['value'];

    $token_replacements = $this->getTokenReplacements();
    $value = strtr($value_with_tokens, $token_replacements);

    return $value == '';
  }

}
