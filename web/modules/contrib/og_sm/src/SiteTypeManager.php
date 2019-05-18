<?php

namespace Drupal\og_sm;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\og\GroupTypeManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A manager to keep track of which content types are og_sm Site enabled.
 */
class SiteTypeManager implements SiteTypeManagerInterface {

  /**
   * The entity storage for node entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * Constructs a SiteTypeManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\og\GroupTypeManager $groupTypeManager
   *   The group type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher, GroupTypeManager $groupTypeManager) {
    $this->nodeTypeStorage = $entityManager->getStorage('node_type');
    $this->eventDispatcher = $eventDispatcher;
    $this->groupTypeManager = $groupTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isSiteTypeId($type_id) {
    $type = $this->nodeTypeStorage->load($type_id);
    if ($type instanceof NodeTypeInterface) {
      return $this->isSiteType($type);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSiteType(NodeTypeInterface $type) {
    return $type->getThirdPartySetting('og_sm', static::SITE_TYPE_SETTING_KEY, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setIsSiteType(NodeTypeInterface $type, $isSiteType) {
    $type->setThirdPartySetting('og_sm', static::SITE_TYPE_SETTING_KEY, $isSiteType);
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteTypes() {
    $types = $this->nodeTypeStorage->loadMultiple();
    $types = array_filter($types, [$this, 'isSiteType']);
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypes() {
    $types = &drupal_static(__FUNCTION__, []);

    if (!empty($types)) {
      return $types;
    }

    try {
      $typeIds = $this->groupTypeManager->getAllGroupContentBundlesByEntityType('node');
    }
    catch (\InvalidArgumentException $exception) {
      return [];
    }

    $sort = $items = [];
    foreach ($typeIds as $typeId) {
      $type = $this->nodeTypeStorage->load($typeId);
      if (!$type) {
        continue;
      }

      $sort[$type->label()] = $type->id();
      $items[$type->id()] = $type;
    }

    // Sort the items.
    ksort($sort);
    foreach ($sort as $key) {
      $types[$key] = $items[$key];
    }

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function isSiteContentType(NodeTypeInterface $type) {
    return $this->groupTypeManager->isGroupContent('node', $type->id());
  }

}
