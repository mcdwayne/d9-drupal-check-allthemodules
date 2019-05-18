<?php

namespace Drupal\global_gateway\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\global_gateway\DisabledRegionsProcessor;
use Drupal\global_gateway\RegionNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the selected language negotiation method for this site.
 */
class RegionNegotiationDefaultForm extends ConfigFormBase {

  /**
   * @var \Drupal\global_gateway\RegionNegotiatorInterface
   */
  protected $negotiator;
  /**
   * Country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;
  /**
   * Disabled regions processor.
   *
   * @var \Drupal\global_gateway\DisabledRegionsProcessor
   */
  protected $disabledRegionsProcessor;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    CountryManagerInterface $country_manager,
    RegionNegotiatorInterface $negotiator,
    DisabledRegionsProcessor $processor
  ) {
    parent::__construct($config_factory);
    $this->countryManager = $country_manager;
    $this->negotiator = $negotiator;
    $this->disabledRegionsProcessor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('country_manager'),
      $container->get('global_gateway_region_negotiator'),
      $container->get('global_gateway.disabled_regions.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_gateway_region_negotiation_configure_default_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['global_gateway.region.negotiation'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $negotiator = $this->negotiator->getNegotiator('default');

    $options = $this->countryManager->getList();
    $this->disabledRegionsProcessor
      ->removeDisabledRegionsFromList($options);
    $form['default_region_code'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Default region'),
      '#default_value' => $negotiator->get('region_code'),
      '#options'       => $options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $negotiator = $this->negotiator->getNegotiator('default');
    $negotiator->set('region_code', $form_state->getValue('default_region_code'));
    $type[$negotiator->id()] = $negotiator->getConfiguration();

    $this->negotiator->saveConfiguration($type);

    parent::submitForm($form, $form_state);
  }

}
