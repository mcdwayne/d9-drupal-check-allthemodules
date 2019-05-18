<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for form enhancers.
 */
abstract class ConfigurableFormEnhancerBase extends FormEnhancerBase implements ConfigurableFormEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function configurationFormValidate(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($event) {
    if ($event == 'configuration_form') {
      return TRUE;
    }
    else {
      return parent::applies($event);
    }
  }

}
