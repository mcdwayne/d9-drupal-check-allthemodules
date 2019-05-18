<?php

namespace Drupal\entity_switcher\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_switcher\SwitcherReferenceFieldItemList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of the 'switcher_reference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "switcher_reference_formatter",
 *   label = @Translation("Switcher reference"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "switcher_reference"
 *   }
 * )
 */
class SwitcherReferenceFormatter extends EntityReferenceEntityFormatter {

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a SwitcherReferenceFormatter instance.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, RequestStack $request_stack) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_type_manager, $entity_display_repository);

    $this->requestStack = $request_stack;
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
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that have a view
    // builder.
    $target_type_data_off = $field_definition->getFieldStorageDefinition()->getSetting('target_type_data_off');
    $target_type_data_on = $field_definition->getFieldStorageDefinition()->getSetting('target_type_data_on');

    return \Drupal::entityTypeManager()->getDefinition($target_type_data_off)->hasViewBuilderClass() &&
      \Drupal::entityTypeManager()->getDefinition($target_type_data_on)->hasViewBuilderClass();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');

    $elements = $entities = [];
    $switchers = $this->getSwitcherEntitiesToView($items, $langcode);
    foreach ($switchers as $delta => $switcher) {
      if (!empty($switcher['data_off']) && !empty($switcher['data_on'])) {
        foreach (['data_off', 'data_on'] as $type) {
          $view_builder = $this->entityTypeManager->getViewBuilder($switcher[$type]->getEntityTypeId());
          $entities[$delta][$type] = $view_builder->view($switcher[$type], $view_mode, $switcher[$type]->language()->getId());

          // Add a resource attribute to set the mapping property's value to the
          // entity's url. Since we don't know what the markup of the entity
          // will be, we shouldn't rely on it for structured data such as RDFa.
          if (!empty($items[$delta]->_attributes) && !$switcher[$type]->isNew() && $switcher[$type]->hasLinkTemplate('canonical')) {
            $items[$delta][$type]->_attributes += ['resource' => $switcher[$type]->toUrl()->toString()];
          }
        }

        /** @var \Drupal\entity_switcher\Entity\SwitcherInterface $switcher_settings */
        $switcher_settings = empty($switcher['switcher']) ? [] : $switcher['switcher'];

        // Get default option and hide options from URL parameters.
        $sop = $this->requestStack->getCurrentRequest()->get('sop');
        $sh = $this->requestStack->getCurrentRequest()->get('sh');
        $default_option = $switcher_settings->getDefaultValue();
        if (!empty($switcher['switcher']) && $sop !== NULL) {
          if (Html::getId($switcher_settings->getDataOff()) == $sop) {
            $default_option = 'data_off';
          }
          elseif (Html::getId($switcher_settings->getDataOn()) == $sop) {
            $default_option = 'data_on';
          }
        }

        $elements[] = [
          '#type' => 'entity_switcher',
          '#data_off' => empty($switcher['switcher']) ? $this->t('On') : $switcher_settings->getDataOff(),
          '#data_on' => empty($switcher['switcher']) ? $this->t('Off') : $switcher_settings->getDataOn(),
          '#default_value' => $default_option,
          '#entity_off' => empty($entities[$delta]['data_off']) ? [] : $entities[$delta]['data_off'],
          '#entity_on' => empty($entities[$delta]['data_on']) ? [] : $entities[$delta]['data_on'],
          '#attributes' => [
            'class' => empty($switcher['switcher']) || empty($switcher_settings->getSliderClasses()) ? ['switcher-default'] : explode(' ', $switcher_settings->getSliderClasses()),
          ],
          '#wrapper_attributes' => [
            'class' => empty($switcher['switcher']) || empty($switcher_settings->getContainerClasses()) ? [] : explode(' ', $switcher_settings->getContainerClasses()),
          ],
          '#cache' => [
            'contexts' => [
              'url.query_args:sop',
              'url.query_args:sh',
            ],
            'tags' => Cache::mergeContexts($switcher['data_off']->getCacheTags(), $switcher['data_on']->getCacheTags()),
          ],
          '#access_switcher' => ($sh === NULL) ? TRUE : FALSE,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    // Collect entity IDs to load. For performance, we want to use a single
    // "multiple entity load" to load all the entities for the multiple
    // "entity reference item lists" being displayed. We thus cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    $ids = [];
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($this->needsEntityLoad($item)) {
          $ids['data_off'][] = $item->data_off_id;
          $ids['data_on'][] = $item->data_on_id;
          $ids['switcher'][] = $item->switcher_id;
        }
      }
    }
    if ($ids) {
      foreach (['data_off', 'data_on', 'switcher'] as $type) {
        $target_type = $this->getFieldSetting('target_type_' . $type);
        $target_entities[$type] = \Drupal::entityTypeManager()->getStorage($target_type)->loadMultiple($ids[$type]);
      }
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        foreach (['data_off', 'data_on', 'switcher'] as $type) {
          if (isset($target_entities[$type][$item->{$type . '_id'}])) {
            $item->{$type} = $target_entities[$type][$item->{$type . '_id'}];
            $item->_loaded = TRUE;
          }
        }
        if ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see ::prepareView()
   * @see ::getEntitiestoView()
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $elements = $this->viewElements($items, $langcode);

    $field_level_access_cacheability = new CacheableMetadata();

    // Try to map the cacheability of the access result that was set at
    // _accessCacheability in getEntitiesToView() to the corresponding render
    // subtree. If no such subtree is found, then merge it with the field-level
    // access cacheability.
    foreach ($items as $delta => $item) {
      // Ignore items for which access cacheability could not be determined in
      // prepareView().
      if (!empty($item->_accessCacheability)) {
        if (isset($elements[$delta])) {
          CacheableMetadata::createFromRenderArray($elements[$delta])
            ->merge($item->_accessCacheability)
            ->applyTo($elements[$delta]);
        }
        else {
          $field_level_access_cacheability = $field_level_access_cacheability->merge($item->_accessCacheability);
        }
      }
    }

    // Apply the cacheability metadata for the inaccessible entities and the
    // entities for which the corresponding render subtree could not be found.
    // This causes the field to be rendered (and cached) according to the cache
    // contexts by which the access results vary, to ensure only users with
    // access to this field can view it. It also tags this field with the cache
    // tags on which the access results depend, to ensure users that cannot view
    // this field at the moment will gain access once any of those cache tags
    // are invalidated.
    $field_level_access_cacheability->merge(CacheableMetadata::createFromRenderArray($elements))
      ->applyTo($elements);

    return $elements;
  }

  /**
   * Returns the referenced entities for display.
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\entity_switcher\SwitcherReferenceFieldItemList $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getSwitcherEntitiesToView(SwitcherReferenceFieldItemList $items, $langcode) {
    $entities = [];

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $cache = new CacheableMetadata();
        foreach (['data_off', 'data_on', 'switcher'] as $type) {
          $entity = $item->{$type};

          // Set the entity in the correct language for display.
          if ($entity instanceof TranslatableInterface) {
            $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
          }

          $access = $this->checkAccess($entity);
          // Add the access result's cacheability, ::view() needs it.
          $cache->merge(CacheableMetadata::createFromObject($access));
          if ($access->isAllowed()) {
            // Add the referring item, in case the formatter needs it.
            $entity->_referringItem = $items[$delta];
            $entities[$delta][$type] = $entity;
          }
        }
        $item->_accessCacheability = $cache;
      }
    }

    return $entities;
  }

}
