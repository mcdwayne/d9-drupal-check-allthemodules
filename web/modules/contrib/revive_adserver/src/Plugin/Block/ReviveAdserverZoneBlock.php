<?php

namespace Drupal\revive_adserver\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\revive_adserver\InvocationMethodServiceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a Revive Adserver Zone Block.
 *
 * @Block(
 *  id = "revive_adserver_zone_block",
 *  admin_label = @Translation("Revive Adserver Zone Block"),
 * )
 */
class ReviveAdserverZoneBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Invocation Method Manager.
   *
   * @var \Drupal\revive_adserver\InvocationMethodServiceManager
   */
  protected $invocationMethodManager;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, InvocationMethodServiceManager $invocationMethodServiceManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->invocationMethodManager = $invocationMethodServiceManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.revive_adserver.invocation_method_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();

    $invocationMethod = $this->invocationMethodManager->loadInvocationMethodFromInput($config['invocation_method']);
    if ($invocationMethod) {
      $invocationMethod->setZoneId($config['zone_id']);
      $invocationMethod->prepare();
      $build = $invocationMethod->render();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $invocation_methods = $this->invocationMethodManager->getInvocationMethodOptionList();

    $zones = $this->invocationMethodManager->getZonesOptionList();

    $form['zone_id'] = [
      '#type' => 'number',
      '#title' => t('Zone'),
      '#description' => t('The Revive Adserver Zone Id.'),
      '#default_value' => isset($config['zone_id']) ? $config['zone_id'] : NULL,
      '#required' => TRUE,
    ];
    // If zones are available, transform number field into a select field.
    if (!empty($zones)) {
      $form['zone_id']['#type'] = 'select';
      $form['zone_id']['#options'] = $zones;
    }

    $form['invocation_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Invocation method'),
      '#description' => $this->t('Banner invocation method. How will the ads be displayed.'),
      '#default_value' => isset($config['invocation_method']) ? $config['invocation_method'] : 'async_javascript',
      '#options' => $invocation_methods,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->setConfigurationValue('zone_id', $form_state->getValue('zone_id'));
      $this->setConfigurationValue('invocation_method', $form_state->getValue('invocation_method'));
    }
  }

}
