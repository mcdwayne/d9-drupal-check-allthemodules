<?php

namespace Drupal\flag_search_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Class FlagSearchApiReindexService.
 */
class FlagSearchApiReindexService implements FlagSearchApiReindexServiceInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FlagSearchApiReindexService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Reindex Item.
   *
   * @param \Drupal\flag\FlaggingInterface $entity
   *   Flagging.
   */
  public function reindexItem(FlaggingInterface $entity) {
    $reindex_on_flagging = $this->configFactory->get('flag_search_api.settings')->get('reindex_on_flagging');
    if ($reindex_on_flagging) {
      $datasource_id = 'entity:' . $entity->getFlaggableType();
      /** @var \Drupal\Core\Entity\ContentEntityInterface $content_flagged */
      $content_flagged = $entity->getFlaggable();
      $indexes = ContentEntity::getIndexesForEntity($content_flagged);

      $entity_id = $entity->getFlaggableId();

      $updated_item_ids = $content_flagged->getTranslationLanguages();
      foreach ($updated_item_ids as $langcode => $language) {
        $inserted_item_ids[] = $langcode;
      }
      $combine_id = function ($langcode) use ($entity_id) {
        return $entity_id . ':' . $langcode;
      };
      $updated_item_ids = array_map($combine_id, array_keys($updated_item_ids));
      foreach ($indexes as $index) {
        if ($updated_item_ids) {
          $index->trackItemsUpdated($datasource_id, $updated_item_ids);
        }
      }
    }
  }

}
