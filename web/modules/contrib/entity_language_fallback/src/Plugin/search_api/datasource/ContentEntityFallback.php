<?php

namespace Drupal\entity_language_fallback\Plugin\search_api\datasource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_language_fallback\FallbackControllerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a datasource which exposes the content entities.
 *
 * In addition to default ContentEntity behavior, indexes content in languages that don't have translations,
 * but have translations in fallback language(s).
 *
 * @SearchApiDatasource(
 *   id = "entity_language_fallback",
 *   deriver = "Drupal\entity_language_fallback\Plugin\search_api\datasource\ContentEntityFallbackDeriver"
 * )
 */
class ContentEntityFallback extends ContentEntity {

  /**
   * Fallback controller
   *
   * @var \Drupal\entity_language_fallback\FallbackControllerInterface
   *
   */
  protected $fallbackController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $datasource */
    $datasource = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $datasource->setFallbackController($container->get('language_fallback.controller'));

    return $datasource;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    $allowed_languages = $this->getLanguages();

    $entity_ids = [];
    foreach ($ids as $item_id) {
      $pos = strrpos($item_id, ':');
      // This can only happen if someone passes an invalid ID, since we always
      // include a language code. Still, no harm in guarding against bad input.
      if ($pos === FALSE) {
        continue;
      }
      $entity_id = substr($item_id, 0, $pos);
      $langcode = substr($item_id, $pos + 1);
      if (isset($allowed_languages[$langcode])) {
        $entity_ids[$entity_id][$item_id] = $langcode;
      }
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $this->getEntityStorage()->loadMultiple(array_keys($entity_ids));
    $items = [];
    $allowed_bundles = $this->getBundles();
    foreach ($entity_ids as $entity_id => $langcodes) {
      if (empty($entities[$entity_id]) || !isset($allowed_bundles[$entities[$entity_id]->bundle()])) {
        continue;
      }
      foreach ($this->languages as $langcode => $language) {
        $item_id = $entity_id . ':' . $langcode;
        if (!in_array($item_id, $ids)) {
          continue;
        }
        if ($entities[$entity_id]->hasTranslation($langcode)) {
          $items[$item_id] = $entities[$entity_id]->getTranslation($langcode)->getTypedData();
          $items[$item_id]->language = $langcode;
        }
        else {
          $source = $this->fallbackController->getTranslation($langcode, $entities[$entity_id]);
          if (!$source) {
            continue;
          }
          $translation = $entities[$entity_id]->addTranslation($langcode, $source->toArray());
          $items[$item_id] = $translation->getTypedData();
          $items[$item_id]->language = $langcode;
        }
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getPartialItemIds($page = NULL, array $bundles = NULL, array $languages = NULL) {
    $parent_items = parent::getPartialItemIds($page, $bundles, $languages);
    if (empty($parent_items)) {
      return $parent_items;
    }
    $entity_ids = [];

    foreach ($parent_items as $parent_item) {
      list($id,) = Utility::splitPropertyPath($parent_item);
      $entity_ids[$id] = 1;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($this->getEntityStorage()->loadMultiple(array_keys($entity_ids)) as $entity_id => $entity) {
      foreach ($this->languages as $langcode => $language) {
        if ($entity->hasTranslation($langcode)) {
          $item_ids[] = "$entity_id:$langcode";
        }
        else {
          $fallback_found = FALSE;
          foreach ($this->fallback_chain[$langcode] as $candidate) {
            if ($entity->hasTranslation($candidate)) {
              $fallback_found = TRUE;
              break;
            }
          }
          if ($fallback_found) {
            $item_ids[] = "$entity_id:$langcode";
          }
        }
      }
    }

    return $item_ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguages() {
    $parent_languages = parent::getLanguages();
    if (!isset($this->languages)) {
      $this->languages = ConfigurableLanguage::loadMultiple(array_keys($parent_languages));
      foreach ($this->languages as $langcode => $language) {
        $this->fallback_chain[$langcode] = $language->getThirdPartySetting('entity_language_fallback', 'fallback_langcodes', []);
      }
    }
    return $parent_languages;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIndexesForEntity(ContentEntityInterface $entity) {
    $datasource_id = 'entity_language_fallback:' . $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $has_bundles = $entity->getEntityType()->hasKey('bundle');

    $indexes = Index::loadMultiple();

    foreach ($indexes as $index_id => $index) {
      // Filter our indexes that don't contain the datasource in question.
      if (!$index->isValidDatasource($datasource_id)) {
        unset($indexes[$index_id]);
      }
      elseif ($has_bundles) {
        // If the entity type supports bundles, we also have to filter out
        // indexes that exclude the entity's bundle.
        $config = $index->getDatasource($datasource_id)->getConfiguration();
        $default = !empty($config['bundles']['default']);
        $bundle_set = in_array($entity_bundle, $config['bundles']['selected']);
        if ($default == $bundle_set) {
          unset($indexes[$index_id]);
        }
      }
    }

    return $indexes;
  }

  /**
   * Set fallback controller instance.
   *
   * @param \Drupal\entity_language_fallback\FallbackControllerInterface $controller
   */
  public function setFallbackController(FallbackControllerInterface $controller) {
    $this->fallbackController = $controller;
  }
}
