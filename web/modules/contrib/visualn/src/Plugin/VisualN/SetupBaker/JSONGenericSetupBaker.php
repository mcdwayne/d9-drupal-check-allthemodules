<?php

/**
 * @file
 * Conatins JSONGenericSetupBaker class.
 */

namespace Drupal\visualn\Plugin\VisualN\SetupBaker;

use Drupal\visualn\Core\SetupBakerBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'JSON Generic Setup Baker' VisualN drawer setup baker.
 *
 * @VisualNSetupBaker(
 *  id = "visualn_json_generic",
 *  label = @Translation("JSON Generic Setup Baker"),
 * )
 */
class JSONGenericSetupBaker extends SetupBakerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'json_setup' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['json_setup'] = [
      '#type' => 'textarea',
      '#title' => t('JSON Setup'),
      // @todo: where to use getConfiguration and where $this->configuration (?)
      //    the same question for other plugin types
      '#default_value' => $this->configuration['json_setup'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function bakeSetup() {
    return json_decode($this->getConfiguration()['json_setup'], TRUE);
  }

}
