<?php

namespace Drupal\referenced_entity_fields_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ReferencedEntityFieldsFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "referenced_entity_fields_formatter",
 *   label = @Translation("Referenced entity fields formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ReferencedEntityFieldsFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManager $entity_field_manager, FormatterPluginManager $formatter_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->formatterPluginManager = $formatter_plugin_manager;
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
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'view_mode' => 'Default',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        'view_mode' => [
          '#type' => 'select',
          '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
          '#title' => t('View mode'),
          '#default_value' => $this->getSetting('view_mode'),
          '#required' => TRUE,
        ],
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = t('Rendered as @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getViewDisplay($bundle_id) {
    if (!isset($this->viewDisplay[$bundle_id])) {

      $display = EntityViewDisplay::create([
        'targetEntityType' => $this->fieldDefinition->getSetting('target_type'),
        'bundle' => $bundle_id,
        'status' => TRUE,
      ]);
      $display->setComponent($this->getSetting('field_name'), [
        'type' => $this->getSetting('type'),
        'settings' => $this->getSetting('settings'),
      ]);
      $this->viewDisplay[$bundle_id] = $display;
    }
    return $this->viewDisplay[$bundle_id];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];

    $entitiesToView = $this->getEntitiesToView($items, $langcode);
    foreach ($entitiesToView as $delta => $entity) {
      if ($entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      /* @var $entity ContentEntityInterface */
      $entityViewDisplay = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
      $fieldsToRender = array_intersect_key($entity->getFields(), $entityViewDisplay->getComponents());
      $paragraphRenderer = $this->entityManager->getViewBuilder('paragraph');
      foreach ($fieldsToRender as $key => $field) {
        if ($entity->get($key) instanceof EntityReferenceRevisionsFieldItemList) {
          $referencedEntities = $entity->get($key)->referencedEntities();
          foreach ($referencedEntities as $inner_key => $referencedEntity) {
            /* @var $referencedEntity Paragraph */
            $referencedEntity = $this->entityManager->getTranslationFromContext($referencedEntity, $langcode);
            $elements[$delta][$key][$inner_key] = $paragraphRenderer->view($referencedEntity);
          }
        }
        else {
          $elements[$delta][$key] = $entity->get($key)
            ->view($entityViewDisplay->getComponent($key));
        }

      }
    }

    return $elements;
  }

  /**
   * @param EntityReferenceFieldItemListInterface $items
   * @param string $langcode
   *
   * @return array
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = $this->entityManager->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }
}
