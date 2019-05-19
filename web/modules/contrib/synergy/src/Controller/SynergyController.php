<?php

namespace Drupal\synergy\Controller;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\Query\QueryException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity translation controllers.
 */
class SynergyController extends ControllerBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * Initializes a content translation controller.
   *
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $manager
   *   A content translation manager instance.
   */
  public function __construct(ContentTranslationManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('content_translation.manager'));
  }

  /**
   * Populates target values with the source values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being translated.
   * @param \Drupal\Core\Language\LanguageInterface $source
   *   The language to be used as source.
   * @param \Drupal\Core\Language\LanguageInterface $target
   *   The language to be used as target.
   */
  public function prepareTranslation(ContentEntityInterface $entity, LanguageInterface $source, LanguageInterface $target) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $source_translation */
    $source_translation = $entity->getTranslation($source->getId());
    $target_translation = $entity->addTranslation($target->getId(), $source_translation->toArray());

    // Make sure we do not inherit the affected status from the source values.
    if ($entity->getEntityType()->isRevisionable()) {
      $target_translation->setRevisionTranslationAffected(NULL);
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityManager()->getStorage('user')->load($this->currentUser()->id());
    $metadata = $this->manager->getTranslationMetadata($target_translation);

    // Update the translation author to current user, as well the translation
    // creation time.
    $metadata->setAuthor($user);
    $metadata->setCreatedTime(REQUEST_TIME);
  }

  /**
   * Builds the translations overview page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @return array
   *   Array of page elements to render.
   */
  public function overview(RouteMatchInterface $route_match, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    // Start collecting the cacheability metadata, starting with the entity and
    // later merge in the access result cacheability metadata.
    $cacheability = CacheableMetadata::createFromObject($entity);
    $rows = $synergies =array();
    $entity_manager = $this->entityManager();

    // Get a list of all entity definitions and filter for just content
    // entities.
    $entity_search_list = array_filter(\Drupal::entityTypeManager()->getDefinitions(),
      function($obj) {
        return $obj instanceof ContentEntityType;
      }
    );

    // Iterate over the list of all ContentEntityType definitions.
    foreach ($entity_search_list as $content_entity) {
      // Get the identifiers for the content entity and the associated config
      // entity that defines bundles.
      /* @var ContentEntityType $content_entity */
      $config_entity_id = $content_entity->getBundleEntityType();
      $content_entity_id = $content_entity->id();

      if (empty($config_entity_id) || empty($content_entity_id)) {
        continue;
      }

      /* Determine whether the current entity has synergy. */

      // Get the storage object for the content entity.
      $storage = $entity_manager->getStorage($content_entity_id);

      // Get the storage object for the config entity thet defines the bundles
      // for the content type.
      $config_entity_storage = $entity_manager->getStorage($config_entity_id);
      // Get all of the bundle types.
      $bundles = $config_entity_storage->getQuery()->execute();

      // Iterate over all bundles, find the entity reference fields and check if
      // they point to the current entity.
      foreach ($bundles as $bundle) {
        try {
          $definitions= $entity_manager->getFieldDefinitions($content_entity_id, $bundle);
          foreach ($definitions as $fieldDefinition) {
            try {
              if ($fieldDefinition->getType() === 'entity_reference') {
                // Possible match.
                $matches = $storage->getQuery()
                  ->condition('type', $bundle)
                  ->condition($fieldDefinition->getName(), $entity->id(), 'IN')
                  ->execute();

                if (empty($matches)) {
                  continue;
                }

                if (isset($synergies[$content_entity_id])) {
                  $synergies[$content_entity_id] += $matches;
                }
                else {
                  $synergies[$content_entity_id] = $matches;
                }
              }
            }
            catch (\Exception $ex) {
              watchdog_exception('synergy', $ex);
            }
          }
        }
        catch (\Exception $ex) {
          watchdog_exception('synergy', $ex);
        }
      }
    }

    // Format the synergies as rows in a table.
    if (!empty($synergies)) {
      foreach ($synergies as $synergy_entity_id => $entity_list) {
        $synergy_storage = $entity_manager->getStorage($synergy_entity_id);
        $entities = $synergy_storage->loadMultiple($entity_list);

        foreach ($entities as $id => $synergy) {
          try {
            $links = $entity_manager->getListBuilder($synergy->getEntityTypeId())
              ->getOperations($synergy);
            $operations = [
              'data' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ];
            $rows[] = [
              $this->l($synergy->label(), $synergy->urlInfo()),
              ucwords(str_replace('_', ' ', $synergy_entity_id)),
              $operations,
            ];
          }
          catch (\Exception $ex) {
            // @todo deal with the likes of paragraphs.
            watchdog_exception('synergy', $ex);
          }
        }
      }
    }

    $panelized_entities = $this->getPanelizerEntities($entity);
    if (!empty($panelized_entities)) {
      $storage = $entity_manager->getStorage('node');
      $synergies = $storage->loadMultiple($panelized_entities);
      if (!empty($synergies)) {
        foreach ($synergies as $id => $synergy) {
          $bundle_label = $entity_manager->getStorage('node_type')->load($synergy->bundle())->label();
          $links = $this->entityManager->getListBuilder($synergy->getEntityTypeId())->getOperations($synergy);
          $operations = array(
            'data' => array(
              '#type' => 'operations',
              '#links' => $links,
            ),
          );
          $rows[] = array($this->l($synergy->label(), $synergy->urlInfo()), ucwords(str_replace('_', ' ', $bundle_label)) , $operations);
        }
      }
      $rows += $panelized_entities;
    }
    $header = array(
      $this->t('Entity'),
      $this->t('Type'),
      $this->t('Operations'),
    );

    $build['#title'] = $this->t('Synergies of %label', array('%label' => $entity->label()));

    // Add metadata to the build render array to let other modules know about
    // which entity this is.
    $build['#entity'] = $entity;
    $cacheability->addCacheTags($entity->getCacheTags())->applyTo($build);

    $build['synergy_overview'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    return $build;
  }

  /**
   * Gets panelized entities.
   *
   * @param $entity
   *
   * @return array
   */
  protected function getPanelizerEntities($entity) {
    $ids = [];
    if (!$this->moduleHandler()->moduleExists('panelizer')) {
      return $ids;
    }
    $uuid = $entity->uuid();
    $query = \Drupal::database()
     ->select('node__panelizer', 'np')
     ->fields('np', ['entity_id'])
     ->condition('panelizer_panels_display', '%' . $uuid . '%', 'LIKE');
    $entities = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($entities as $entity_row) {
      $ids[] = $entity_row['entity_id'];
    }
    return $ids;
  }

}
