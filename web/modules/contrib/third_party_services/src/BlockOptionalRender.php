<?php

namespace Drupal\third_party_services;

use Drupal\Core\Block\BlockPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic implementation of BlockOptionalRenderInterface.
 */
class BlockOptionalRender implements BlockOptionalRenderInterface {

  /**
   * Instance of the "MODULE.mediator" service.
   *
   * @var MediatorInterface
   */
  protected $mediator;
  /**
   * Counters for blocks of the same type.
   *
   * @var int[]
   */
  private static $counters = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(MediatorInterface $mediator) {
    $this->mediator = $mediator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('third_party_services.mediator'));
  }

  /**
   * {@inheritdoc}
   */
  public function process(array &$element, BlockPluginInterface $block) {
    $plugin_id = $block->getPluginId();

    $response = $this->mediator->placeholder($element, $element, $plugin_id, $this->updateCounter($plugin_id));

    if (NULL !== $response) {
      // Keep settings because anonymous user will not be able to load them!
      // @see drupalSettingsLoader.js
      $element['#attached']['drupalSettings'] = $response->getAttachments()['drupalSettings'];
    }
  }

  /**
   * Count blocks of the same type on the page.
   *
   * @param string $plugin_id
   *   Block ID.
   *
   * @return int
   *   Incremented/initial number of (not allowed only) blocks of given type.
   */
  protected function updateCounter(string $plugin_id) {
    self::$counters[$plugin_id] = self::$counters[$plugin_id] ?? 0;

    return self::$counters[$plugin_id]++;
  }

}
