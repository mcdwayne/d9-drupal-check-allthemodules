<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\drd\ContextProvider\RouteContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for DRD blocks.
 */
abstract class Base extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Route context of current request.
   *
   * @var \Drupal\drd\ContextProvider\RouteContext
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $context = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      RouteContext::findDrdContext()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Determine if the current request is within the context of a DRD entity.
   *
   * @return bool
   *   TRUE if context is within a DRD entity.
   */
  protected function isDrdContext() {
    return $this->context ? (bool) $this->context->getEntity() : FALSE;
  }

  /**
   * Get the entity of the current context.
   *
   * @return bool|\Drupal\drd\Entity\BaseInterface
   *   The DRD entity if within an entity context or FALSE otherwise.
   */
  protected function getEntity() {
    return $this->context ? $this->context->getEntity() : FALSE;
  }

}
