<?php

namespace Drupal\layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

class DefaultConfigLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'class' => '',
      'full_width' => FALSE,
    ];
  }

  public function build(array $regions) {
    $build = parent::build($regions);
    if (!empty($this->configuration['class'])) {
      $build['#attributes']['class'][] = $this->configuration['class'];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra Classes'),
      '#default_value' => $this->configuration['class'],
    ];
    $form['full_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Full width'),
      '#default_value' => $this->configuration['full_width'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['class'] = $form_state->getValue('class');
    $this->configuration['full_width'] = $form_state->getValue('full_width');
  }

}
