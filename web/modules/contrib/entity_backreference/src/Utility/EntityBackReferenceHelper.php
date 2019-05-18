<?php

namespace Drupal\entity_backreference\Utility;

use Drupal\search_api\Entity\Index;
use Drupal\Core\Entity\EntityInterface;

class EntityBackReferenceHelper {
  /**
   * Check if there is need for reindexing on given entity
   *
   * @param EntityInterface $entity
   * @param string $action
   */
  public static function checkForIndex(EntityInterface $entity,$action = 'update'){
    $reindex = \Drupal::config('entity_backreference.settings')->get('entity_updates_reindex');
    if($reindex){
      $entity_reference_fields = self::referencingFields($entity->getEntityTypeId(),$entity->bundle());
      $indexes = Index::loadMultiple();
      /** @var \Drupal\search_api\IndexInterface[] $indexes Load all indexes */
      foreach($entity_reference_fields as $real_field_name => $field){
        //go through every index and check if field exists in it
        foreach($indexes as $index){
          /** @var Index $index */
          $field_exists = $index->getField($real_field_name);
          //if field exists on index and we can force to index it
          if($field_exists){
            //we need to convert field name back to real field name
            $real_field_name = str_replace($entity->getEntityTypeId().'_'.$entity->bundle().'_','',$real_field_name);
            switch ($action){
              case 'insert':
              case 'delete':
                self::indexItems($index,$entity->get($real_field_name)->referencedEntities());
                break;
              case 'update':
                //compare original and updated and update only changed indexes
                $entities = self::compare($entity->original->get($real_field_name)->referencedEntities(),$entity->get($real_field_name)->referencedEntities());
                if(!empty($entities)){
                  self::indexItems($index,$entities);
                }
                break;
            }
          }
        }
      }
    }
  }

  /**
   * Get Entity Reference Fields on Entity By Entity Type and Bundle
   * Note: returning array has keys like: entity_id_bundle_field_name
   *
   * @param string $entity_type_id
   * @param string $entity_bundle_id
   *
   * @return array Returns fields array
   */
  public static function referencingFields($entity_type_id, $entity_bundle_id) {
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $entity_reference_fields = $entity_field_manager->getFieldMapByFieldType('entity_reference');
    $fields = [];
    if(isset($entity_reference_fields[$entity_type_id])){
      foreach($entity_reference_fields[$entity_type_id] as $field_name => $field){
        if(isset($field['bundles'])){
          foreach($field['bundles'] as $bundle){
            if($bundle == $entity_bundle_id){
              $fields[$entity_type_id.'_'.$entity_bundle_id.'_'.$field_name]= $field;
            }
          }
        }
      }
    }
    return $fields;
  }

  /**
   *  Compares data from in Entity Reference field and returns prepared data for indexing
   *
   * @param EntityInterface[] $original Array of Original Referencing Entities
   * @param EntityInterface[] $updated  Array Of Referencing Entities
   *
   * @return array ['inserted'=> '', 'deleted'=>']
   */
  public static function compare($original,$updated){
    $for_insertion = [];
    $for_deletion = [];
    foreach($original as $entity_original){
      $exists_in_updated = FALSE;
      foreach($updated as $entity_updated){
        if($entity_original->id() === $entity_updated->id()){
          $exists_in_updated = TRUE;
        }
      }
      if(!$exists_in_updated){
        $for_deletion[$entity_original->id()] = $entity_original;
      }
    }

    foreach($updated as $entity_updated){
      $exists_in_original = FALSE;
      foreach($original as $entity_original){
        if($entity_original->id() === $entity_updated->id()){
          $exists_in_original = TRUE;
        }
      }
      if(!$exists_in_original){
        $for_insertion[$entity_updated->id()] = $entity_updated;
      }
    }

    return array_merge($for_insertion,$for_deletion);
  }

  /**
   * Indexes items/entities in given index with certain action
   *
   * @param Index $index
   * @param EntityInterface[] $entities
   */
  public static function indexItems($index,$entities){
    foreach($entities as  $entity){
      $datasource_id = 'entity:'. $entity->getEntityTypeId();
      $referenced_entity_id = $entity->id();
      $updated_item_ids = $entity->getTranslationLanguages();
      foreach ($updated_item_ids as $langcode => $language) {
        $inserted_item_ids[] = $langcode;
      }
      $combine_id = function ($langcode) use ($referenced_entity_id) {
        return $referenced_entity_id . ':' . $langcode;
      };
      $items_for_indexing = array_map($combine_id, array_keys($updated_item_ids));

      $index->trackItemsUpdated($datasource_id,$items_for_indexing);
    }
  }
}
