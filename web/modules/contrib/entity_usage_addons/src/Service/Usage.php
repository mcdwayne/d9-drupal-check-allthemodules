<?php

namespace Drupal\entity_usage_addons\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Entity Usage Addons Usage.
 *
 * @package Drupal\entity_usage_addons\Service
 */
class Usage {

  use StringTranslationTrait;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  private $entityUsage;
  private $entityTypeManager;

  /**
   * Usage Class constructor.
   *
   * @param \Drupal\entity_usage\EntityUsage $entityUsage
   *   Entity Usage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(EntityUsage $entityUsage,
                              EntityTypeManagerInterface $entityTypeManager,
                              LoggerChannelFactoryInterface $loggerFactory) {

    // Entity Usage.
    $this->entityUsage = $entityUsage;

    // Entity Manager.
    $this->entityTypeManager = $entityTypeManager;

    // Logger Factory.
    $this->loggerFactory = $loggerFactory;

  }

  /**
   * Usage Getter.
   *
   * @param string $entityType
   *   Entity Type.
   * @param int $entityId
   *   Entity Id.
   *
   * @return array
   *   Return array of usage Ids.
   */
  public function getUsage($entityType, $entityId) {
    $entity = $this->entityTypeManager->getStorage($entityType)->load($entityId);

    if ($entity) {
      $allUsages = $this->entityUsage->listUsage($entity);
      return $allUsages;
    }

    return [];
  }

  /**
   * Linked Usage.
   *
   * @param string $entityType
   *   Entity Type.
   * @param int $entityId
   *   Entity ID.
   *
   * @return \Drupal\Core\GeneratedLink
   *   Link.
   */
  public function linkedUsage($entityType, $entityId) {
    $route = "entity.{$entityType}.entity_usage";
    $url = Url::fromRoute($route, [$entityType => $entityId]);
    $itemCount = $this->getUsageTotal($entityType, $entityId);
    $link = Link::fromTextAndUrl($itemCount, $url);

    return $link->toString();
  }

  /**
   * Generate Detailed usage.
   *
   * @param string $entityType
   *   Entity Type.
   * @param int $entityId
   *   Entity ID.
   * @param array $showFields
   *   Fields array.
   * @param bool $showHeader
   *   Show header setting.
   *
   * @return array|bool
   *   Return the themed array or 0.
   */
  public function detailedUsage($entityType, $entityId, array $showFields, $showHeader) {
    $rows = [];
    $header = [];

    $allUsages = $this->getUsage($entityType, $entityId);
    // If there is no usage.
    if (empty($allUsages)) {
      return 0;
    }

    foreach ($allUsages as $sourceType => $ids) {
      // Loop over every usage entry for this entity.
      foreach ($ids as $key => $value) {
        $sourceEntity = $this->entityTypeManager->getStorage($sourceType)->load($key);
        $row = [];

        // Show Entity field.
        if (in_array('entity', $showFields)) {
          if (!empty($sourceEntity->hasLinkTemplate('canonical'))) {
            $link = $sourceEntity->toLink();
          }
          else {
            // TODO If we have a paragraph, resolve the url to the parent entity
            // For now we will simply display a link.
            // See Issue #3000184.
            $link = $sourceEntity->label();
          }

          $row[] = $link;

          if (!array_key_exists('entity', $header)) {
            $header['entity'] = $this->t('Entity');
          }
        }

        // Show Status field.
        if (in_array('status', $showFields)) {
          if (isset($sourceEntity->status)) {
            $published = !empty($sourceEntity->status->value) ? $this->t('Published') : $this->t('Unpublished');
          }
          else {
            $published = '';
          }

          $row[] = $published;

          // Build the header only once.
          if (!array_key_exists('status', $header)) {
            $header['status'] = $this->t('Status');
          }
        }

        // Show Type field.
        if (in_array('type', $showFields)) {
          $row[] = $sourceEntity->getEntityTypeId();

          if (!array_key_exists('type', $header)) {
            $header['type'] = $this->t('Type');
          }
        }

        $rows[] = $row;
      }
    }

    // Render Table.
    $build = [
      // TODO Add logic to get list.
      '#theme' => 'table',
      '#rows' => $rows,
    ];

    // Add header if required.
    if ($showHeader) {
      $build['#header'] = $header;
    }

    return $build;
  }

  /**
   * Usage Total.
   *
   * @param string $entityType
   *   Entity Type.
   * @param int $entityId
   *   Entity ID.
   *
   * @return int
   *   Total.
   */
  public function getUsageTotal($entityType, $entityId) {
    $allUsages = $this->getUsage($entityType, $entityId);
    $itemCount = 0;

    // If there is usage.
    if (!empty($allUsages)) {
      foreach ($allUsages as $ids) {
        // Count all usages.
        $itemCount += count($ids);
      }
    }

    return $itemCount;
  }

}
