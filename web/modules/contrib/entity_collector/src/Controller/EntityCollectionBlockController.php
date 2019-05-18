<?php

namespace Drupal\entity_collector\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EntityCollectionBlockController implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * Entity Collection Manager.
   *
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  private $entityCollectionManager;

  /**
   * EntityCollectorApiController constructor.
   *
   * @param \Drupal\entity_collector\Service\EntityCollectionManagerInterface $entityCollectionManager
   *   The entity collection manager.
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(AccountProxyInterface $currentUser, RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, EntityCollectionManagerInterface $entityCollectionManager) {
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->entityCollectionManager = $entityCollectionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity_collection.manager')
    );
  }

  /**
   * Refresh the collection block for the given element id.
   *
   * @param string $blockElementId
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Exception
   */
  public function refresh($blockId) {
    $response = new AjaxResponse();
    $lockName = 'entity_collection_block_' . $blockId . '_' . $this->currentUser->id();
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      /** @var \Drupal\block\Entity\Block $entityCollectionBlock */
      $entityCollectionBlock = $this->getEntityCollectionBlock($blockId);
      $build = $this->getEntityCollectionBlockBuild($entityCollectionBlock);
      $render = $this->renderer->renderRoot($build);
      $render = trim($render);

      if (!empty($render)) {
        $response->addCommand(new ReplaceCommand('.js-entity-collection-block[data-block-id="' . $blockId . '"]', $render));
      }
    }
    finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return $response;
  }

  /**
   * Get the block entity id by the element id.
   *
   * @param string $blockElementId
   *
   * @return string
   */
  private function getBlockId($blockElementId) {
    $prefix = 'block-';
    $end = '--';
    $blockId = $blockElementId;
    if (substr($blockElementId, 0, strlen($prefix)) == $prefix) {
      $blockId = substr($blockElementId, strlen($prefix));
    }

    if (strpos($blockId, $end)) {
      $blockIdSegments = explode($end, $blockId);
      $blockId = reset($blockIdSegments);
    }

    return $blockId;
  }

  /**
   * Get the Entity Collection Block render array.
   *
   * @param $blockId
   *
   * @return \Drupal\block\Entity\Block
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getEntityCollectionBlock($blockId) {
    /** @var \Drupal\block\Entity\Block $block */
    $block = $this->entityTypeManager->getStorage('block')
      ->load($blockId);

    return $block;
  }

  /**
   * Get the Entity Collection Block render array.
   *
   * @param \Drupal\block\Entity\Block $entityCollectionBlock
   *
   * @return array
   */
  private function getEntityCollectionBlockBuild(Block $entityCollectionBlock) {
    $build = $this->entityTypeManager
      ->getViewBuilder('block')
      ->view($entityCollectionBlock);
    if (empty($build)) {
      return [];
    }

    return $build;
  }

}
