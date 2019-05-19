<?php

/**
 * @file
 * Contains \Drupal\viewmode_field\Plugin\Field\FieldFormatter\EntityReferenceEntityViewModeFormatter.
 */

namespace Drupal\viewmode_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsFormatterBase;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_view_mode_view",
 *   label = @Translation("Rendered entity view mode"),
 *   description = @Translation("Display the referenced entities rendered by entity_view() and a custom view mode."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 *
 * @see \Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter
 */
class EntityReferenceEntityViewModeFormatter extends EntityReferenceRevisionsFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')
          ->error(
            'Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', array(
              '@entity_type' => $entity->getEntityTypeId(),
              '@entity_id' => $entity->id(),
            )
          );
        return $elements;
      }

      $view_mode = $this->getViewMode($entity);
      if ($entity->id()) {
        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $elements[$delta] = $view_builder->view(
          $entity, $view_mode, $entity->language()
          ->getId()
        );

        // Add a resource attribute to set the mapping property's value to the
        // entity's url. Since we don't know what the markup of the entity will
        // be, we shouldn't rely on it for structured data such as RDFa.
        if (!empty($items[$delta]->_attributes)) {
          $items[$delta]->_attributes += array('resource' => $entity->url());
        }
      }
      else {
        // This is an "auto_create" item.
        $elements[$delta] = array('#markup' => $entity->label());
      }
      $depth = 0;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that have a view
    // builder.
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');

    return \Drupal::entityManager()
      ->getDefinition($target_type)
      ->hasViewBuilderClass();
  }

  /**
   * Returns the view_mode for selected entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   The entity to search for viewmode.
   *
   * @return string
   *   Found viewmode or full if no viewmode field is found.
   */
  private function getViewMode(ContentEntityBase $entity) {
    $fields = $entity->getFieldDefinitions();

    $found = NULL;
    foreach ($fields as $field) {
      if ($field instanceof FieldConfig && 'view_mode' === $field->getType()) {
        $found = $field;
        break;
      }
    }

    $view_mode = 'full';
    if ($found != NULL) {
      $view_mode = $entity->{$found->getName()}[0]->getValue();
    }

    return $view_mode;
  }

}
