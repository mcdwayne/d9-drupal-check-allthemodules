<?php

namespace Drupal\consent\Plugin\Block;

use Drupal\consent\Oil\OilConfigBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConsentLayerBlock.
 *
 * @Block(
 *   id = "oil_consent_layer",
 *   admin_label = @Translation("Consent Layer"),
 *   category = @Translation("Consent")
 * )
 */
class OilConsentLayerBlock extends ConsentLayerBlockBase {

  /**
   * The OIL.js configuration builder.
   *
   * @var \Drupal\consent\Oil\OilConfigBuilderInterface
   */
  protected $oilConfigBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setOilConfigBuilder($container->get('consent.oil_config'));
    return $instance;
  }

  /**
   * Set the OIL config builder.
   *
   * @param \Drupal\consent\Oil\OilConfigBuilderInterface $config_builder
   *   The OIL config builder.
   */
  public function setOilConfigBuilder(OilConfigBuilderInterface $config_builder) {
    $this->oilConfigBuilder = $config_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return parent::build() + [
      'config' => $this->oilConfigBuilder->buildConfigTag($this->configuration['oil']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return parent::blockForm($form, $form_state) + [
      'oil' => [
        '#type' => 'fieldset',
        '#title' => $this->t('OIL.js configuration parameters'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      ] + $this->oilConfigBuilder->configFormElements($this->configuration['oil']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['oil'] = $form_state->getValue('oil', []);
    unset($this->configuration['oil']['publicPath']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = parent::defaultConfiguration() + [
      'oil' => \Drupal::service('consent.oil_config')->defaultValues(),
    ];
    unset($default['oil']['publicPath']);
    return $default;
  }

}
