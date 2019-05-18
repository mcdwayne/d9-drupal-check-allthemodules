<?php

namespace Drupal\consent_iframe\Form;

use Drupal\consent\Oil\OilConfigBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OilConsentIframeForm.
 */
class OilConsentIframeForm extends ConsentIframeFormBase {

  /**
   * The OIL config builder.
   *
   * @var \Drupal\consent\Oil\OilConfigBuilderInterface
   */
  protected $oilConfigBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setOilConfigBuilder($container->get('consent.oil_config'));
    return $instance;
  }

  /**
   * Set the OIL.js configuration builder.
   *
   * @param \Drupal\consent\Oil\OilConfigBuilderInterface $config_builder
   *   The OIL.js configuration builder.
   */
  public function setOilConfigBuilder(OilConfigBuilderInterface $config_builder) {
    $this->oilConfigBuilder = $config_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::$configId);
    $form = parent::buildForm($form, $form_state);

    $oil_values = $config->get('oil') ? $config->get('oil') : $this->oilConfigBuilder->defaultValues();
    $form['oil'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OIL.js configuration parameters'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="block"]' => ['value' => '___none'],
        ],
      ],
    ] + $this->oilConfigBuilder->configFormElements($oil_values);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $block_id = $form_state->getValue('block');
    if (empty($block_id) || $block_id === '___none') {
      $config = $this->config(static::$configId);
      $config->set('oil', $form_state->getValue('oil'));
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConsentBlocks() {
    return $this->blockStorage->loadByProperties(['plugin' => 'oil_consent_layer']);
  }

}
