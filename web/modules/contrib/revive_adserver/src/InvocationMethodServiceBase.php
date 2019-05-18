<?php

namespace Drupal\revive_adserver;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Revive Invocation Method service plugins.
 */
abstract class InvocationMethodServiceBase extends PluginBase implements InvocationMethodServiceInterface, ContainerFactoryPluginInterface {

  /**
   * The Revive Zone Id.
   *
   * @var int
   */
  protected $zoneId;

  /**
   * The zone width.
   *
   * @var int
   */
  protected $width;

  /**
   * The zone height.
   *
   * @var int
   */
  protected $height;

  /**
   * The unique id for the invocation.
   *
   * @var string
   */
  protected $uniqueId;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Invocation Method Manager.
   *
   * @var \Drupal\revive_adserver\InvocationMethodServiceManager
   */
  protected $invocationMethodManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\revive_adserver\InvocationMethodServiceManager $invocationMethodServiceManager
   *   Invocation method manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $configFactory, InvocationMethodServiceManager $invocationMethodServiceManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->invocationMethodManager = $invocationMethodServiceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.revive_adserver.invocation_method_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * @inheritdoc
   */
  abstract public function render();

  /**
   * Set the Zone Id.
   *
   * @param int $zoneId
   *   Revive Zone id.
   */
  public function setZoneId($zoneId) {
    $this->zoneId = $zoneId;
  }

  /**
   * Get the Zone Id.
   *
   * @return int
   *   Revive Zone id.
   */
  public function getZoneId() {
    return $this->zoneId;
  }

  /**
   * Set the Zone width.
   *
   * @param int $width
   *   Zone width.
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  /**
   * Get the Zone width.
   *
   * @return  int
   *   Zone width.
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * Set the Zone height.
   *
   * @param int $height
   *   Zone height.
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  /**
   * Get the Zone height.
   *
   * @return int
   *   Zone height.
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Load the zone and set dimensions.
   */
  public function prepare() {
    if ($zone = $this->invocationMethodManager->getZoneFromConfig($this->getZoneId())) {
      $this->setWidth($zone['width']);
      $this->setHeight($zone['height']);
    }
  }

  /**
   * Returns the base delivery path based on the stored configuration.
   *
   * @return string
   *   Delivery path.
   */
  protected function getReviveDeliveryPath() {
    $config = $this->configFactory->get('revive_adserver.settings');
    $delivery_url = $config->get('delivery_url');
    return '//' . $delivery_url;
  }

  /**
   * Returns the Revive Id based on the Revive internals.
   *
   * @return string
   *   md5 hashed id.
   */
  protected function getReviveId() {
    $config = $this->configFactory->get('revive_adserver.settings');
    $delivery_url = $config->get('delivery_url');
    $delivery_url_ssl = $config->get('delivery_url_ssl');

    // Build the Revive Id based on their internal implementation.
    $reviveId = md5($delivery_url . '*' . $delivery_url_ssl);
    return $reviveId;
  }

  /**
   * Some invocation methods require an unique id as an identifier.
   *
   * @return bool|string
   *   Unique id.
   */
  protected function getUniqueId() {
    if (empty($this->uniqueId)) {
      $this->uniqueId = substr(md5(uniqid('', 1)), 0, 7);
    }
    return $this->uniqueId;
  }

  /**
   * Returns the fallback link url.
   *
   * @return string
   *   Link href url.
   */
  protected function getLinkHref() {
    $randomNumber = Crypt::randomBytesBase64();
    $url = $this->getReviveDeliveryPath() . '/ck.php?n=' . $this->getUniqueId() . '&amp;cb=' . $randomNumber;
    return $url;
  }

  /**
   * Returns the src of the fallback banner image.
   *
   * @return string
   *   Image src url.
   */
  protected function getImageSrc() {
    $randomNumber = Crypt::randomBytesBase64();
    $url = $this->getReviveDeliveryPath() . '/avw.php?zoneid=' . $this->getZoneId() . '&amp;cb=' . $randomNumber . '&amp;n=' . $this->getUniqueId();
    return $url;
  }

}
