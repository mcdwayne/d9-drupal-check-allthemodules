<?php

/**
 * @file
 * Contains \Drupal\weathercomau\Plugin\Block\WeatherWidgetBlock.
 */

namespace Drupal\weathercomau\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "weathercomau_widget_block",
 *   admin_label = @Translation("Weather widget")
 * )
 */
class WeatherWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a WeatherWidgetBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
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
    return array(
      'state' => '',
      'city' => '',
      'days' => '',
      'current' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_location'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Weather.com.au location settings'),
    );

    $form['block_location']['state'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#default_value' => $this->configuration['state'],
      '#required' => TRUE,
    );

    $form['block_location']['city'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => $this->configuration['city'],
      '#required' => TRUE,
    );

    $form['block_display'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Weather.com.au display settings'),
    );

    $form['block_display']['days'] = array(
      '#type' => 'select',
      '#title' => $this->t('Show forecast for the next'),
      '#options' => array('Day', 'Two days', 'Three days'),
      '#default_value' => $this->configuration['days'],
      '#empty_option' => $this->t('- None -'),
    );

    $form['block_display']['current'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show current conditions?'),
      '#default_value' => $this->configuration['current'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $block_location = $form_state->getValue('block_location');
    $this->configuration['state'] = $block_location['state'];
    $this->configuration['city'] = $block_location['city'];

    $block_display = $form_state->getValue('block_display');
    $this->configuration['days'] = $block_display['days'];
    $this->configuration['current'] = $block_display['current'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();

    if (\Drupal::currentUser()->hasPermission('view weathercomau widgets')) {
      $build = array(
        '#theme' => 'weathercomau_widget_block',
        '#configuration' => $this->configuration,
      );
    }

    return $build;
  }

}
