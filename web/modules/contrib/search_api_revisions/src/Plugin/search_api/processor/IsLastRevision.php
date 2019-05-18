<?php

namespace Drupal\search_api_revisions\Plugin\search_api\processor;

use Drupal\search_api_revisions\Plugin\search_api\datasource\ContentEntityRevisions;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an additional field containing the term parents.
 *
 * @SearchApiProcessor(
 *   id = "is_last_revision",
 *   label = @Translation("Revision is last"),
 *   description = @Translation("Checks whether saved revision is last."),
 *   stages = {
 *   "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   },
 * )
 */
class IsLastRevision extends ProcessorPluginBase {

  /**
   * Moderation information.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $etmi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));
    return $plugin;
  }

  /**
   * Stores Moderation information in protected property.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etmi
   *   Entity type manager.
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $etmi) {
    $this->etmi = $etmi;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->etmi ?: \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!($datasource instanceof ContentEntityRevisions)) {
      return $properties;
    }
    $definition = [
      'label' => $this->t('Is last revision?'),
      'description' => $this->t('Whether this revision is last or not.'),
      'type' => 'boolean',
      'processor_id' => $this->getPluginId(),
    ];
    $properties['is_last_revision'] = new ProcessorProperty($definition);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    if (!($object instanceof ComplexDataInterface)) {
      return;
    }
    /** @var \Drupal\Core\Entity\RevisionableContentEntityBase $entity */
    $entity = $object->getValue();
    $entity->updateLoadedRevisionId();

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), 'entity_revision:' . $entity->getEntityTypeId(), 'is_last_revision');

    $entity_revisions = \Drupal::entityQuery($entity->getEntityTypeId())
      ->allRevisions()
      ->accessCheck(FALSE)
      ->condition('nid', $entity->id())
      ->execute();

    foreach ($fields as $field) {
      if ($field->getDatasourceId() == 'entity_revision:' . $entity->getEntityTypeId()) {
        if (!count($entity_revisions)) {
          $field->addValue(TRUE);
        }
        elseif ($entity->getLoadedRevisionId() == max(array_keys($entity_revisions))) {
          $field->addValue(TRUE);
        }
        else {
          $field->addValue(FALSE);
        }
      }
    }
  }

}
