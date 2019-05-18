<?php

namespace Drupal\commerce_vl\Plugin\Block;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_vl\ViralLoopsIntegratorInterface;

/**
 * Provides a 'ViralLoopsBlock' block.
 *
 * @Block(
 *  id = "viral_loops_block",
 *  admin_label = @Translation("Viral Loops Block"),
 * )
 */
class ViralLoopsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\commerce_vl\ViralLoopsIntegratorInterface definition.
   *
   * @var \Drupal\commerce_vl\ViralLoopsIntegratorInterface
   */
  protected $viralLoopsIntegrator;

  /**
   * JSON serialization service.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $json;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The cart provider service.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new ViralLoopsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_vl\ViralLoopsIntegratorInterface $commerce_vl_integrator
   *   The service for Viral Loops integration.
   * @param \Drupal\Component\Serialization\SerializationInterface $json_serializer
   *   JSON serialization service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger channel factory.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViralLoopsIntegratorInterface $commerce_vl_integrator,
    SerializationInterface $json_serializer,
    LoggerChannelFactoryInterface $logger_factory,
    CartProviderInterface $cart_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viralLoopsIntegrator = $commerce_vl_integrator;
    $this->json = $json_serializer;
    $this->logger = $logger_factory->get('commerce_vl');
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_vl.integrator'),
      $container->get('serialization.json'),
      $container->get('logger.factory'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'dcom-viral-loops',
        'style' => ['height:0;', 'width:0;'],
      ],
      '#attached' => ['library' => ['commerce_vl/viral_loops_scripts']],
    ];

    // Widget.
    $block['#attributes']['data-vl-widget'] = $this->json->encode($this->viralLoopsIntegrator->getWidgetData() ?: FALSE);

    // Client side identification.
    $block['#attributes']['data-vl-client-identify-user'] = $this->json->encode($this->viralLoopsIntegrator->getClientIdentifyUserData() ?: FALSE);

    // Logging out.
    $block['#attributes']['data-vl-logout'] = $this->json->encode($this->viralLoopsIntegrator->needLogout());

    return ['viral_loops_block' => $block];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = ['user', 'url.site', 'cart'] + $this->cartsCacheableMetadata()->getCacheContexts();
    return Cache::mergeContexts(parent::getCacheContexts(), $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), $this->cartsCacheableMetadata()->getCacheTags());
  }

  /**
   * Collect Commerce Carts cacheable metadata.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   Carts metadata.
   */
  protected function cartsCacheableMetadata() {
    $metadata = new CacheableMetadata();
    foreach ($this->cartProvider->getCarts() as $cart) {
      $metadata->addCacheableDependency($cart);
    }
    return $metadata;
  }

}
