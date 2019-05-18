<?php

namespace Drupal\freshdesk\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'FreshdeskWidget' block.
 *
 * @Block(
 *  id = "freshdesk_widget",
 *  admin_label = @Translation("Freshdesk widget"),
 * )
 */
class FreshdeskWidget extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'queryString' => "&widgetType=popup",
      'utf8'        => "✓",
      'widgetType'  => "popup",
      'buttonType'  => "text",
      'buttonText'  => "Support",
      'buttonColor' => "white",
      'buttonBg'    => "#006063",
      'alignment'   => "6",
      'offset'      => "235px",
      'formHeight'  => "500px",
      'url'         => "https://ncwit.freshdesk.com"
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    foreach ($this->defaultConfiguration() as $key => $value) {
      $form[$key] = [
        '#type' => 'textfield',
        '#title' => $this->t($key),
        '#default_value' => $this->configuration[$key],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    foreach ($this->defaultConfiguration() as $key => $value) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#attached']['library'][] = 'freshdesk/embed';
    $build['#attached']['drupalSettings']['freshdesk'] = $this->configuration;

    return $build;
  }

}
