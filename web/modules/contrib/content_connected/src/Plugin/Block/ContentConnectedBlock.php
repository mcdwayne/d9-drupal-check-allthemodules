<?php

/**
 * @file
 * Contains \Drupal\content_connected\Plugin\Block\ContentConnectedBlock.
 */

namespace Drupal\content_connected\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\content_connected\ContentConnectedManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a 'ContentConnectedBlock' block.
 *
 * @Block(
 *  id = "content_connected_block",
 *  admin_label = @Translation("Content connected block"),
 * )
 */
class ContentConnectedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The contentconnected manager.
   *
   * @var \Drupal\content_connected\ContentConnectedManagerInterface
   */
  protected $contentConnectedmanager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\content_connected\ContentConnectedManagerInterface $content_connected_manager
   *   The book manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ContentConnectedManagerInterface $content_connected_manager, EntityStorageInterface $node_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contentConnectedmanager = $content_connected_manager;
    $this->requestStack = $request_stack;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('request_stack'), $container->get('content_connected.manager'), $container->get('entity.manager')->getStorage('node')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content connected');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $build['content_connected_block']['#markup'] = $this->contentConnectedmanager->renderMatches($node->id());
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
