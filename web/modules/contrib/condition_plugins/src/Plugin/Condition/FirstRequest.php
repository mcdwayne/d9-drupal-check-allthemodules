<?php

namespace Drupal\condition_plugins\Plugin\Condition;

use Drupal\Component\Utility\Crypt;
use Drupal\condition_plugins\PrivateTempStoreFactory;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'First request' condition.
 *
 * @Condition(
 *   id = "condition_plugins_first_request",
 *   label = @Translation("First request"),
 * )
 */
class FirstRequest extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Deny any page caching.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * The user PrivateTempStore object.
   *
   * @var \Drupal\condition_plugins\PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a FirstRequest condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\condition_plugins\PrivateTempStoreFactory $private_temp_store
   *   The user PrivateTempStore object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   Deny any page caching.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PrivateTempStoreFactory $private_temp_store, RequestStack $request_stack, KillSwitch $page_cache_kill_switch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->killSwitch = $page_cache_kill_switch;
    $this->privateTempStore = $private_temp_store->get('condition_plugins');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('condition_plugins.private_tempstore'),
      $container->get('request_stack'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['status' => 0] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('First page request'),
      '#default_value' => $this->configuration['status'],
      '#description' => $this->t('Check if it is the first time the user visits the page. After 5 minutes the registration expires.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['status'] = $form_state->getValue('status');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['status'])) {
      return $this->t('Do not return true if it is not the first time the user visits the page.');
    }

    return $this->t('Return true if it is the first time the user visits the page.');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    static $visited = FALSE;

    // Deny any page caching on the current request.
    $this->killSwitch->trigger();

    if ($visited === FALSE && $this->configuration['status']) {
      $uri = Crypt::hashBase64($this->requestStack->getCurrentRequest()->getRequestUri());

      $visited = $this->privateTempStore->get($uri);
      if ($visited === NULL) {
        $this->privateTempStore->set($uri, TRUE);
      }

      return !(boolean) $visited;
    }

    return !(boolean) $visited;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
