<?php

namespace Drupal\entity_reference_ajax_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_ajax_entity_view",
 *   label = @Translation("Rendered Entity Ajax Formatter"),
 *   description = @Translation("Display the referenced entities rendered by entity_view() with extra options including ajax loading."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceAjaxFormatter extends EntityReferenceEntityFormatter implements ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Constructs a EntityReferenceAjaxFormatter instance.
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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route
   *   The current route.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, CurrentRouteMatch $current_route) {
    $this->currentRoute = $current_route;
    if ($current_route->getRouteName() === 'entity_reference_ajax_formatter.ajax_field') {
      $label = 'hidden';
    }
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_type_manager, $entity_display_repository);
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'load_more' => FALSE,
      'max' => 0,
      'number' => 6,
      'sort' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of entities to load'),
      '#step' => 1,
      '#default_value' => $this->getSetting('number'),
      '#required' => TRUE,
    ];
    $elements['sort'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort'),
      '#options' => [
        $this->t('Field Default Order'),
        $this->t('Random'),
        $this->t('Date Modified (ascending)'),
        $this->t('Date Modified (descending)'),
        $this->t('Date Created (ascending)'),
        $this->t('Date Created (descending)'),
      ],
      '#default_value' => $this->getSetting('sort'),
      '#required' => TRUE,
    ];
    $elements['load_more'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load More'),
      '#default_value' => $this->getSetting('load_more'),
      '#description' => $this->t('Provide load more by AJAX functionality.'),
    ];
    $elements['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max'),
      '#description' => $this->t('The maximum to load via load more. Select 0 for unlimited.'),
      '#default_value' => max($this->getSetting('max'), $this->getSetting('number') + 1),
      '#states' => [
        'visible' => [
          ':input[name$="[load_more]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#element_validate' => [
        [$this, 'settingsMaxValidate'],
      ],
    ];

    return $elements;
  }

  /**
   * Use element validator to make sure that hex values are in correct format.
   *
   * @param array $element
   *   The Default colors element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function settingsMaxValidate(array $element, FormStateInterface $form_state) {
    $max = $form_state->getValue($element['#parents']);
    if ($max === 0) {
      return;
    }
    $form = $element['#parents'];
    array_pop($form);
    $values = $form_state->getValue($form);
    if ($values['load_more']) {
      if ($max <= $values['number']) {
        $form_state->setError($element, $this->t('Max must be greater than the initial load number if Load More is enabled. You can set to 0 no limit.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Loading %items', [
      '%items' => $this->getSetting('number'),
    ]);
    if ($this->getSetting('sort')) {
      $summary[] = $this->t('Sort: %sort', [
        '%sort' => [
          $this->t('Field Default Order'),
          $this->t('Random'),
          $this->t('Date Modified (ascending)'),
          $this->t('Date Modified (descending)'),
          $this->t('Date Created (ascending)'),
          $this->t('Date Created (descending)'),
        ][$this->getSetting('sort')],
      ]);
    }

    if ($this->getSetting('load_more')) {
      $summary[] = $this->t('Load more button enabled');
      if ($this->getSetting('max')) {
        $summary[] = $this->t('Maximum entities to load: %max', [
          '%max' => $this->getSetting('max'),
        ]);
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];
    $parameters = $this->currentRoute->getParameters();
    $start = ($parameters->get('start')) ? $parameters->get('start') : 0;
    $printed = [];
    if ($parameters->get('printed')) {
      $printed = explode('-', $parameters->get('printed'));
    }
    $count = $this->getSetting('number');
    $max = $this->getSetting('max');
    $id = "ajax-field-{$this->fieldDefinition->getTargetEntityTypeId()}-{$items->getEntity()->id()}-{$this->fieldDefinition->getName()}";

    $entities = $this->getEntitiesToView($items, $langcode);
    switch ($this->getSetting('sort')) {
      case 1:
        shuffle($entities);
        break;

      case 2:
        usort($entities, function (ContentEntityInterface $a, ContentEntityInterface $b) {
          if (method_exists($a, 'getChangedTime') && method_exists($b, 'getChangedTime')) {
            return $a->getChangedTime() <=> $b->getChangedTime();
          }
          // If entity doesn't have changed time record, return order unchanged.
          return 0;
        });
        break;

      case 3:
        usort($entities, function (ContentEntityInterface $a, ContentEntityInterface $b) {
          if (method_exists($a, 'getChangedTime') && method_exists($b, 'getChangedTime')) {
            return -1 * ($a->getChangedTime() <=> $b->getChangedTime());
          }
          // If entity doesn't have changed time record, return order unchanged.
          return 0;
        });
        rsort($entities);
        break;

      case 4:
        usort($entities, function ($a, $b) {
          if (method_exists($a, 'getCreatedTime') && method_exists($b, 'getCreatedTime')) {
            return $a->getCreatedTime() <=> $b->getCreatedTime();
          }
          // If entity doesn't have created time record, return order unchanged.
          return 0;
        });
        break;

      case 5:
        usort($entities, function ($a, $b) {
          if (method_exists($a, 'getCreatedTime') && method_exists($b, 'getCreatedTime')) {
            return -1 * ($a->getCreatedTime() <=> $b->getCreatedTime());
          }
          // If entity doesn't have created time record, return order unchanged.
          return 0;
        });
        break;
    }

    foreach ($entities as $delta => $entity) {
      if (count($elements) >= $count) {
        break;
      }
      if ($this->getSetting('sort') === 1) {
        if (in_array($entity->id(), $printed)) {
          continue;
        }
        $printed[] = $entity->id();
      }
      elseif ($start > $delta) {
        continue;
      }
      if ($max && count($elements) + $start >= $max) {
        break;
      }

      // Due to render caching and delayed calls, the viewElements() method
      // will be called later in the rendering process through a '#pre_render'
      // callback, so we need to generate a counter that takes into account
      // all the relevant information about this field and the referenced
      // entity that is being rendered.
      $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId()
        . $items->getFieldDefinition()->getTargetBundle()
        . $items->getName()
        // We include the referencing entity, so we can render default images
        // without hitting recursive protections.
        . $items->getEntity()->id()
        . $entity->getEntityTypeId()
        . $entity->id();

      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }

      // Protect ourselves from recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%entity_id' => $entity->id(),
          '%field_name' => $items->getName(),
          '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
        ]);
        return $elements;
      }

      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += ['resource' => $entity->toUrl()->toString()];
      }
    }
    $total = $count + $start;
    if ($this->getSetting('load_more') && count($items) > $total && (!$max || $max > $total)) {
      $elements[$delta + 1] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'text-align-center',
            'ajax-field-entity-ref',
          ],
          'id' => $id,
        ],
      ];
      $elements[$delta + 1]['more'] = [
        '#type' => 'link',
        '#title' => t('Load More'),
        '#url' => Url::fromRoute('entity_reference_ajax_formatter.ajax_field', [
          'entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
          'entity' => $items->getEntity()->id(),
          'field_name' => $this->fieldDefinition->getName(),
          'view_mode' => $this->viewMode,
          'language' => $langcode,
          'start' => $start + count($elements) - 1,
          'printed' => implode('-', $printed),
        ]),
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
      ];
      $elements['#attached']['library'][] = 'core/drupal.ajax';
    }

    return $elements;
  }

}
