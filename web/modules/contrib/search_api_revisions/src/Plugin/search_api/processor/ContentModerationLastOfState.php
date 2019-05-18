<?php

namespace Drupal\search_api_revisions\Plugin\search_api\processor;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\TypedData\ComplexDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an additional field containing the term parents.
 *
 * @SearchApiProcessor(
 *   id = "content_moderation_last_of_state",
 *   label = @Translation("Content moderation - last of state"),
 *   description = @Translation("Adds flag whether this is last revision in the state."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 * )
 */
class ContentModerationLastOfState extends ProcessorPluginBase {

  /**
   * Moderation information.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->setModerationInformation($container->get('content_moderation.moderation_information'));
    return $plugin;
  }

  /**
   * Stores Moderation information in protected property.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $information
   *   Moderation information.
   */
  protected function setModerationInformation(ModerationInformationInterface $information) {
    $this->moderationInformation = $information;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!($datasource instanceof ContentEntity)) {
      return $properties;
    }
    if ($datasource->getEntityTypeId() == 'node') {
      $definition = [
        'label' => $this->t('Content moderation - last of state'),
        'description' => "Checks whether this revision is latest in its state.",
        'type' => 'boolean',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['content_moderation_last_of_state'] = new ProcessorProperty($definition);
    }

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
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), 'entity:node', 'content_moderation_last_of_state');
    if (empty($fields)) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), 'entity_revision:node', 'content_moderation_last_of_state');
    }

    foreach ($fields as $field) {
      if (in_array($field->getDatasourceId(), ['entity:node', 'entity_revision:node'])) {

        /** @var \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList $moderation_state */
        $moderation_state = $object->get('moderation_state');

        /** @var \Drupal\Core\Entity\RevisionableContentEntityBase $entity */
        $entity = $object->getValue();

        // @TODO: use drupal_static here?
        $cms_revisions = \Drupal::entityQuery('content_moderation_state')
          ->allRevisions()
          ->condition('content_entity_id', $entity->id())
          ->condition('moderation_state', $moderation_state->get(0)->getValue()['value'])
          ->execute();

        $cms_storage = \Drupal::entityTypeManager()->getStorage('content_moderation_state');
        $entity_revisions_ids = [];
        foreach (array_keys($cms_revisions) as $cms_revision_id) {
          /** @var \Drupal\content_moderation\Entity\ContentModerationState $cms_revision */
          $cms_revision = $cms_storage->loadRevision($cms_revision_id);
          $entity_revisions_ids[] = $cms_revision->content_entity_revision_id->value;
        }

        if ($entity->getLoadedRevisionId() == max($entity_revisions_ids)) {
          $field->addValue(TRUE);
        }
        else {
          $field->addValue(FALSE);
        }
      }
    }
  }

  /**
   * Calculate dependencies method.
   *
   * @return array
   *   Dependencies as array.
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', 'content_moderation');
    return $this->dependencies;
  }

}
