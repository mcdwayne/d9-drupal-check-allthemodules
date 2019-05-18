<?php

namespace Drupal\measuremail;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\Plugin\MeasuremailElementsBase;

/**
 * Provides a base class for configurable measuremail elements.
 *
 * @see \Drupal\measuremail\Annotation\MeasuremailElements
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementInterface
 * @see \Drupal\measuremail\MeasuremailElementsInterface
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsBase
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsManager
 * @see plugin_api
 */
abstract class ConfigurableMeasuremailElementBase extends MeasuremailElementsBase implements ConfigurableMeasuremailElementInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
