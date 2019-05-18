<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\entity_processor;

use Drupal\content_synchronizer\Events\ImportEvent;
use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @EntityProcessor(
 *   id = "content_synchronizer_taxonomy_term_processor",
 *   entityType = "taxonomy_term"
 * )
 */
class TaxonomyTermProcessor extends EntityProcessorBase {

  /**
   * The dependencies buffer.
   *
   * @var array
   */
  protected static $dependenciesBuffer = [];

  /**
   * Tree buffer.
   *
   * @var array
   */
  protected static $treeBuffer = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Listen import event.
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->addListener(ImportEvent::ON_ENTITY_IMPORTER, [
      $this,
      'onImportedEntity'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityToImport(array $data, EntityInterface $existingEntity = NULL) {

    // Init tree process.
    $currentGid = $data[ExportEntityWriter::FIELD_GID];
    $defaultLanguageData = $this->getDefaultLanguageData($data, FALSE);
    if (array_key_exists('parents', $defaultLanguageData)) {

      /** @var \Drupal\content_synchronizer\Processors\ImportProcessor $importProcessor */
      $importProcessor = ImportProcessor::getCurrentImportProcessor();

      /** @var \Drupal\content_synchronizer\Entity\ImportEntity $import */
      $import = $importProcessor->getImport();

      foreach ($defaultLanguageData['parents'] as $parentGid) {
        // If the entity to reference is currently importing, then we cannot add it to the reference because it probably do not have an id yet.
        if ($import->gidIsCurrentlyImporting($parentGid)) {
          $this->addParentDependencie($data, $parentGid);
        }
        // The entity has already been imported, so we add it to the field.
        elseif ($import->gidHasAlreadyBeenImported($parentGid)) {
          static::$treeBuffer[$currentGid][] = $this->getGlobalReferenceManager()
            ->getEntityByGid($parentGid)
            ->id();
        }
        // The entity has not been imported yet, so we iport it.
        else {
          // Get the plugin of the entity :
          /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase $plugin */
          $plugin = $this->getEntityProcessorManager()
            ->getInstanceByEntityType($this->getGlobalReferenceManager()
              ->getEntityTypeFromGid($parentGid));
          if ($entityData = $import->getEntityDataFromGid($parentGid)) {
            $parent = $plugin->import($entityData);
            static::$treeBuffer[$currentGid][] = $parent->id();
          }
        }
      }
    }

    return parent::getEntityToImport($data, $existingEntity);
  }

  /**
   * Add parent dependencie to avoid circular dependencies.
   *
   * @param array $data
   *   The child array data.
   * @param string $parentGid
   *   The parent gid.
   */
  protected function addParentDependencie(array $data, $parentGid) {
    static::$dependenciesBuffer[$parentGid][] = $data;
  }

  /**
   * Action on Entity import end.
   *
   * @param \Drupal\content_synchronizer\Events\ImportEvent $event
   *   The event.
   */
  public function onImportedEntity(ImportEvent $event) {
    $gid = $event->getGid();
    $entity = $event->getEntity();
    // Add parent dependencies.
    if (array_key_exists($gid, static::$dependenciesBuffer)) {
      foreach (static::$dependenciesBuffer[$gid] as $childData) {
        $child = $this->getGlobalReferenceManager()
          ->getEntityByGid($childData[ExportEntityWriter::FIELD_GID]);
        $alreadyAddedParents = $this->getParentsTerms($child);
        $alreadyAddedParents[] = $entity->id();
        $child->set('parent', $alreadyAddedParents);
        $child->save();
      }
    }

    // Save data.
    if (array_key_exists($gid, static::$treeBuffer)) {
      $entity->set('parent', static::$treeBuffer[$gid]);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDataToExport(EntityInterface $entityToExport) {
    // Init data to export:
    $data = parent::getDataToExport($entityToExport);

    // Get parents Terms.
    $data['parents'] = [];
    $parents = $this->getParentsTerms($entityToExport);
    if (!empty($parents)) {
      $plugin = $this->getEntityProcessorManager()
        ->getInstanceByEntityType($entityToExport->getEntityTypeId());
      foreach ($parents as $parent) {
        if ($parentGid = $plugin->export($parent)) {
          $data['parents'][] = $parentGid;
        }
      }
    }

    return $data;
  }

  /**
   * Return the list of parents terms.
   *
   * @param \Drupal\taxonomy\Entity\Term $child
   *   The child term.
   *
   * @return mixed
   *   The parents terms.
   */
  protected function getParentsTerms(Term $child) {
    return \Drupal::entityTypeManager()
      ->getStorage($child->getEntityTypeId())
      ->loadParents($child->id());
  }

}
