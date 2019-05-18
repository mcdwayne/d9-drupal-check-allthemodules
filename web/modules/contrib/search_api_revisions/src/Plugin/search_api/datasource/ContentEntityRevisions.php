<?php

namespace Drupal\search_api_revisions\Plugin\search_api\datasource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Represents a datasource which exposes the content entities.
 *
 * @SearchApiDatasource(
 *   id = "entity_revision",
 *   deriver = "Drupal\search_api_revisions\Plugin\search_api\datasource\ContentEntityRevisionsDeriver"
 * )
 */
class ContentEntityRevisions extends ContentEntity {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $type = $this->getEntityTypeId();
    $properties = $this->getEntityFieldManager()->getBaseFieldDefinitions($type);
    if ($bundles = array_keys($this->getBundles())) {
      foreach ($bundles as $bundle_id) {
        $properties += $this->getEntityFieldManager()->getFieldDefinitions($type, $bundle_id);
      }
    }
    // Exclude properties with custom storage, since we can't extract them
    // currently, due to a shortcoming of Core's Typed Data API. See #2695527.
    foreach ($properties as $key => $property) {
      if ($property->getFieldStorageDefinition()->hasCustomStorage()) {
        unset($properties[$key]);
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $items */
    $items = [];
    foreach ($ids as $item_id) {
      $pos = strrpos($item_id, ':');
      // This can only happen if someone passes an invalid ID, since we always
      // include a language code. Still, no harm in guarding against bad input.
      if ($pos === FALSE) {
        continue;
      }
      /* $entity_id = substr($item_id, 0, $pos); */
      $revision_id = substr($item_id, $pos + 1);

      if ($entity_revision = $this->getEntityStorage()->loadRevision($revision_id)) {
        $items[$item_id] = $entity_revision->getTypedData();
      }
    }

    return $items;
  }

  /**
   * Retrieves the entity from a search item.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   An item of this datasource's type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object represented by that item, or NULL if none could be
   *   found.
   */
  protected function getEntity(ComplexDataInterface $item) {
    $value = $item->getValue();
    return $value instanceof EntityInterface ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId(ComplexDataInterface $item) {
    if ($entity = $this->getEntity($item)) {
      $enabled_bundles = $this->getBundles();
      if (isset($enabled_bundles[$entity->bundle()])) {
        $revision_key = $this->getEntityType()->getKey('revision');
        return $entity->id() . ':' . $entity->{$revision_key};
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function checkItemAccess(ComplexDataInterface $item, AccountInterface $account = NULL) {
    if ($entity = $this->getEntity($item)) {
      return $this->getEntityTypeManager()
        ->getAccessControlHandler($this->getEntityTypeId())
        ->access($entity, 'view', $account);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPartialItemIds($page = NULL, array $bundles = NULL, array $languages = NULL) {
    $select = \Drupal::entityQuery($this->getEntityTypeId());

    // We want to determine all entities of either one of the given bundles OR
    // one of the given languages. That means we can't just filter for $bundles
    // if $languages is given. Instead, we have to filter for all bundles we
    // might want to include and later sort out those for which we want only the
    // translations in $languages and those (matching $bundles) where we want
    // all revisions.
    if ($this->hasBundles()) {
      $bundle_property = $this->getEntityType()->getKey('bundle');
      if ($bundles && !$languages) {
        $select->condition($bundle_property, $bundles, 'IN');
      }
      else {
        $enabled_bundles = array_keys($this->getBundles());
        // Since this is also called for removed bundles/languages,
        // $enabled_bundles might not include $bundles.
        if ($bundles) {
          $enabled_bundles = array_unique(array_merge($bundles, $enabled_bundles));
        }
        if (count($enabled_bundles) < count($this->getEntityBundles())) {
          $select->condition($bundle_property, $enabled_bundles, 'IN');
        }
      }
    }

    if (isset($page)) {
      $page_size = $this->getConfigValue('tracking_page_size');
      assert('$page_size', 'Tracking page size is not set.');
      $select->range($page * $page_size, $page_size);
    }

    $entity_ids = $select->execute();

    if (!$entity_ids) {
      return NULL;
    }

    // For all loaded entities, compute all their item IDs (one for each
    // translation we want to include). For those matching the given bundles (if
    // any), we want to include translations for all enabled languages. For all
    // other entities, we just want to include the translations for the
    // languages passed to the method (if any).
    $item_ids = [];

    $entity_type = $this->getEntityType();
    $entity_revision_table = $entity_type->getRevisionDataTable();
    $entity_revision_key = $entity_type->getKey('revision');
    $entity_id_key = $entity_type->getKey('id');

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($this->getEntityStorage()->loadMultiple($entity_ids) as $entity_id => $entity) {
      $select = \Drupal::database()->select($entity_revision_table, 'ert');
      $select->addField('ert', $entity_revision_key, 'revision');
      $select->condition($entity_id_key, $entity_id);
      foreach ($select->execute()->fetchAll() as $item) {
        $item_ids[] = $entity_id . ':' . $item->revision;
      }
    }
    return $item_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function viewItem(ComplexDataInterface $item, $view_mode, $langcode = NULL) {
    try {
      if ($entity = $this->getEntity($item)) {
        $langcode = $langcode ?: $entity->language()->getId();
        return $this->getEntityTypeManager()->getViewBuilder($this->getEntityTypeId())->view($entity, $view_mode, $langcode);
      }
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultipleItems(array $items, $view_mode, $langcode = NULL) {
    try {
      $view_builder = $this->getEntityTypeManager()->getViewBuilder($this->getEntityTypeId());
      // Langcode passed, use that for viewing.
      if (isset($langcode)) {
        $entities = [];
        foreach ($items as $i => $item) {
          if ($entity = $this->getEntity($item)) {
            $entities[$i] = $entity;
          }
        }
        if ($entities) {
          return $view_builder->viewMultiple($entities, $view_mode, $langcode);
        }
        return [];
      }
      // Otherwise, separate the items by language, keeping the keys.
      $items_by_language = [];
      foreach ($items as $i => $item) {
        if ($item instanceof EntityInterface) {
          $items_by_language[$item->language()->getId()][$i] = $item;
        }
      }
      // Then build the items for each language. We initialize $build beforehand
      // and use array_replace() to add to it so the order stays the same.
      $build = array_fill_keys(array_keys($items), []);
      foreach ($items_by_language as $langcode => $language_items) {
        $build = array_replace($build, $view_builder->viewMultiple($language_items, $view_mode, $langcode));
      }
      return $build;
    }
    catch (\Exception $e) {
      // The most common reason for this would be a
      // \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException in
      // getViewBuilder(), because the entity type definition doesn't specify a
      // view_builder class.
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getIndexesForEntity(ContentEntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $datasource_id = 'entity_revision:' . $entity_type;
    $entity_bundle = $entity->bundle();
    $has_bundles = $entity->getEntityType()->hasKey('bundle');

    // Needed for PhpStorm. See https://youtrack.jetbrains.com/issue/WI-23395.
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = Index::loadMultiple();

    foreach ($indexes as $index_id => $index) {
      // Filter our indexes that don't contain the datasource in question.
      if (!$index->isValidDatasource($datasource_id)) {
        unset($indexes[$index_id]);
      }
      elseif ($has_bundles) {
        // If the entity type supports bundles, we also have to filter out
        // indexes that exclude the entity's bundle.
        try {
          $config = $index->getDatasource($datasource_id)->getConfiguration();
          $default = !empty($config['bundles']['default']);
          $bundle_set = in_array($entity_bundle, array_filter($config['bundles']['selected'], function($data) { return !empty($data); }));
          if ($default == $bundle_set) {
            unset($indexes[$index_id]);
          }
        }
        catch (SearchApiException $e) {
          unset($indexes[$index_id]);
        }
      }
    }

    return $indexes;
  }

}
