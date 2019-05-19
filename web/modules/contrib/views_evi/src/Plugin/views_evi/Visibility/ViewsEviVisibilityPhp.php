<?php

namespace Drupal\views_evi\Plugin\views_evi\Visibility;

use Drupal\views_evi\ViewsEviHandlerTokenInterface;
use Drupal\views_evi\ViewsEviVisibilityInterface;

/**
 * @ViewsEviVisibility(
 *   id = "php",
 *   title = "Visibility from PHP",
 * )
 */
class ViewsEviVisibilityPhp extends ViewsEviVisibilityTokenBase implements ViewsEviHandlerTokenInterface, ViewsEviVisibilityInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($settings, &$form) {
    $settings_form = parent::settingsForm($settings, $form);

    // Return our plugin settings form.
    $settings_form['php'] = array(
      '#type' => 'textarea',
      '#title' => t('Visibility PHP code'),
      '#description' => t('Do not use &lt;?php tags and return a boolean value. You can use $identifier, $view, $display_handler, $filter_handler$evi and $tokens. The brave even alter $form[$identifier] and $form[\'#info\']["filter-$id"].'),
      '#default_value' => $settings['php'],
      '#disabled' => !\Drupal::currentUser()->hasPermission('use php for views_evi'),
    );
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return array('php' => 'return empty($tokens[$identifier]);');
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility(&$form) {
    $filter_wrapper = $this->getFilterWrapper();
    $settings = $filter_wrapper->getPluginSettings('visibility');
    $php_value = $settings['php'];
    $identifier = $filter_wrapper->getIdentifier();
    $id = $filter_wrapper->getId();
    $tokens = $this->getTokenReplacements();
    $display_handler = $filter_wrapper->getDisplayHandler();
    $filter_handler = $filter_wrapper->getFilterHandler();
    $evi = $filter_wrapper->getEvi();
    $view = $display_handler->view;

    $visibility = eval($php_value);
    return $visibility;
  }

}
