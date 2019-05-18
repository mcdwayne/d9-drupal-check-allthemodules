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
 *   id = "content_moderation_state",
 *   label = @Translation("Content moderation state"),
 *   description = @Translation("Adds all content moderation states found on node."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 * )
 */
class ContentModerationState extends ProcessorPluginBase {

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
        'label' => $this->t('Content moderation state'),
        'description' => "Moderation state of current node revision.",
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['content_moderation_state'] = new ProcessorProperty($definition);
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
      ->filterForPropertyPath($item->getFields(), 'entity:node', 'content_moderation_state');
    if (empty($fields)) {
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), 'entity_revision:node', 'content_moderation_state');
    }

    /** @var \Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList $moderation_state */
    $moderation_state = $object->get('moderation_state');
    foreach ($fields as $field) {
      if (in_array($field->getDatasourceId(), ['entity:node', 'entity_revision:node'])) {
        $field->addValue($moderation_state->get(0)->getValue()['value']);
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
