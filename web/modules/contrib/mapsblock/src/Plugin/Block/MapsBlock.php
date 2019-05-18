<?php

namespace Drupal\mapsblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MapsBlock' block.
 *
 * @Block(
 *  id = "maps_block",
 *  admin_label = @Translation("Maps block"),
 * )
 */
class MapsBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Variable to access the config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MapsBlock object.
   *
   * @param array $configuration
   *   Config variable.
   * @param string $plugin_id
   *   Config variable.
   * @param mixed $plugin_definition
   *   Config variable.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Address'),
      '#default_value' => $this->configuration['address'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '2',
      '#required' => TRUE,
    ];

    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#default_value' => $this->configuration['country'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => $this->configuration['city'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['state'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#default_value' => $this->configuration['state'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['zipcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zipcode'),
      '#default_value' => $this->configuration['zipcode'],
      '#maxlength' => 64,
      '#size' => 64,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['address'] = $form_state->getValue('address');
    $this->configuration['country'] = $form_state->getValue('country');
    $this->configuration['city'] = $form_state->getValue('city');
    $this->configuration['state'] = $form_state->getValue('state');
    $this->configuration['zipcode'] = $form_state->getValue('zipcode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $google_api_key = $this->configFactory->get('mapsblock.mapsblockconfiguration')
      ->get('google_map_api_key');
    $address = $this->configuration['address'] . "+" .
      $this->configuration['country'] . "+" .
      $this->configuration['city'] . "+" .
      $this->configuration['state'] . "+" .
      $this->configuration['zipcode'];
    return [
      '#theme' => 'mapsblock',
      '#address' => $address,
      '#api_key' => $google_api_key,
    ];
  }

}
