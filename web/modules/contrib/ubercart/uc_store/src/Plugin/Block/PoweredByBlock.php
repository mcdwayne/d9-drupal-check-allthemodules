<?php

namespace Drupal\uc_store\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\PrivateKey;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to identify Ubercart as the store software on a site.
 *
 * @Block(
 *   id = "powered_by_ubercart",
 *   admin_label = @Translation("Powered by Ubercart")
 * )
 */
class PoweredByBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The site's private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The site's private key.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateKey $private_key, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->privateKey = $private_key;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('private_key'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Contents of block don't depend on the page or user or any other
    // cache context we have available.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'message' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $configuration = $this->configuration;

    $form['message'] = [
      '#type' => 'radios',
      '#title' => $this->t('Footer message for store pages'),
      '#options' => array_merge(
        [0 => $this->t('Randomly select a message from the list below.')],
        $this->options()
      ),
      '#default_value' => isset($configuration['message']) ? $configuration['message'] : '',
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = $this->configuration['message'];

    // Figure out what page is being viewed.
    $path = $this->routeMatch->getRouteName();

    $messages = $this->options();

    if ($id == 0) {
      // Calculate which message to show based on a hash of the path and the
      // site's private key. The message initially chosen for each page on a
      // specific site will thus be pseudo-random, yet we will consistently
      // display the same message on any given page on that site, thus allowing
      // pages to be cached.
      $private_key = $this->privateKey->get();
      $id = (hexdec(substr(md5($path . $private_key), 0, 2)) % count($messages)) + 1;
    }

    return ['#markup' => $messages[$id]];
  }

  /**
   * Returns the default message options.
   */
  protected function options() {
    $url = [':url' => Url::fromUri('https://www.drupal.org/project/ubercart')->toString()];
    return [
      1 => $this->t('<a href=":url">Powered by Ubercart</a>', $url),
      2 => $this->t('<a href=":url">Drupal e-commerce</a> provided by Ubercart.', $url),
      3 => $this->t('Supported by Ubercart, an <a href=":url">open source e-commerce suite</a>.', $url),
      4 => $this->t('Powered by Ubercart, the <a href=":url">free shopping cart software</a>.', $url),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    return ['url'];
  }

}
