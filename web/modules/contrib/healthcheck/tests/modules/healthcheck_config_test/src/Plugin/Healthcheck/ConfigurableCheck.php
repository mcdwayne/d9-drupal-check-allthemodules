<?php

namespace Drupal\healthcheck_config_test\Plugin\healthcheck;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;

/**
 * @Healthcheck(
 *  id = "config_test",
 *  label = @Translation("Configurable Check test"),
 *  description = "A test module providing a configurable check.",
 *  tags = {
 *   "testing",
 *  }
 * )
 */
class ConfigurableCheck extends HealthcheckPluginBase {
  
  use StringTranslationTrait;

  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['status'] = FindingStatus::NOT_PERFORMED;

    return $config;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('status'),
      '#description' => $this->t('The finding status to emit when the check is run.'),
      '#options' => FindingStatus::getLabels(),
      '#size' => 1,
      '#default_value' => $this->configuration['status'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  public function getFindings() {
    $findings = [];
    $status = $this->configuration['status'];

    $findings[] = new Finding($status, $this, $this->getPluginId(), $this->t(
     'Config check is @status', [
       '@status' => FindingStatus::numericToConstant($status),
      ]
    ));

    return $findings;
  }

}
