<?php

namespace Drupal\entity_reference_layout\Plugin\Field\FieldWidget;

use Drupal\file\Entity\File;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Drupal\entity_reference_layout\Event\ErlPropertiesFormEvent;

/**
 * Entity Reference with Layout field widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_layout_widget",
 *   label = @Translation("Entity reference layout (With layout builder)"),
 *   description = @Translation("Layout builder for paragraphs."),
 *   field_types = {
 *     "entity_reference_layout_revisioned"
 *   },
 *   multiple_values = true
 * )
 */
class EntityReferenceLayoutWidget extends InlineEntityFormComplex {

  /**
   * The List of allowed Layouts.
   *
   * @var array
   */
  protected $allowedLayouts;

  /**
   * The Layouts Manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * The Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an EntityReferenceLayoutWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Layout\LayoutPluginManager $layoutPluginManager
   *   Layout plugin manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   Current logged in user.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ModuleHandlerInterface $module_handler,
    LayoutPluginManager $layoutPluginManager,
    EventDispatcherInterface $eventDispatcher,
    Renderer $renderer,
    AccountProxy $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $entity_type_bundle_info, $entity_type_manager, $entity_display_repository, $module_handler);
    $this->layoutPluginManager = $layoutPluginManager;
    $this->eventDispatcher = $eventDispatcher;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('module_handler'),
      $container->get('plugin.manager.core.layout'),
      $container->get('event_dispatcher'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Prepare the form state.
   *
   * First calls parent method, then loads
   * layout info into $form_state from items.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   * @param bool $translating
   *   Whether there's a translation in progress.
   */
  protected function prepareFormState(FormStateInterface $form_state, FieldItemListInterface $items, $translating = FALSE) {

    parent::prepareFormState($form_state, $items, $translating);
    $entities = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'entities',
    ]);
    foreach ($items as $delta => $item) {
      if (isset($entities[$delta]) && !isset($entities[$delta]['layout_properties'])) {
        $layout_properties = [
          'options' => [],
          'options' => isset($item->options) ? $item->options : [],
          'config' => !empty($item->config) ? $item->config : [],
          'region' => isset($item->region) ? $item->region : '',
          'layout' => isset($item->layout) ? $item->layout : '',
          'entity_bundle' => $item->entity->bundle(),
          'parent' => -1,
        ];
        $form_state->set([
          'inline_entity_form',
          $this->getIefId(),
          'entities',
          $delta,
          'layout_properties',
        ], $layout_properties);
      }
    }

  }

  /**
   * Stores new or edited entity in $form_state.
   *
   * Stored temporarily in $form_state, saved to db
   * when container entity is saved down the line.
   *
   * Except for the lines that save layout info with entity,
   * this is copied directly from the InlineEntityFormComplex class.
   */
  public static function submitSaveEntity($entity_form, FormStateInterface $form_state) {

    parent::submitSaveEntity($entity_form, $form_state);

    $ief_id = $entity_form['#ief_id'];
    $layout_name = $form_state->getValue(array_merge($entity_form['#parents'], ['layout_selection', 'layout']));
    $layout_properties = $form_state->getValue(array_merge($entity_form['#parents'], ['layout_properties']));
    $layout_options = $form_state->getValue(array_merge($entity_form['#parents'], ['layout_options']));

    // Pass layout config to plugin submit handler for processing.
    $layout_config = [];
    if ($layout_name && !empty($entity_form['layout_config']['config'])) {
      $layout_instance = \Drupal::service('plugin.manager.core.layout')->createInstance($layout_name);
      if ($layout_instance instanceof PluginFormInterface) {
        $subform_state = SubformState::createForSubform($entity_form['layout_config']['config'], $form_state->getCompleteForm(), $form_state);
        $layout_instance->submitConfigurationForm($entity_form['layout_config']['config'], $subform_state);
        $layout_config = $layout_instance->getConfiguration();
      }
    }

    if (in_array($entity_form['#op'], ['add', 'duplicate'])) {
      // Saving a new entity so set delta to last item.
      $entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']);
      $delta = count($entities) - 1;
    }
    else {
      $delta = $entity_form['#ief_row_delta'];
    }

    $layout_properties = [
      'layout' => $layout_name,
      'region' => $layout_properties['region'],
      'parent' => $layout_properties['parent'],
      'config' => $layout_config,
      'options' => $layout_options,
      'entity_bundle' => $entity_form['#entity']->bundle(),
    ];
    $form_state->set([
      'inline_entity_form',
      $ief_id,
      'entities',
      $delta,
      'layout_properties',
    ], $layout_properties);
  }

  /**
   * Build the form widget.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if (empty($element['entities']['#attached']['library'])) {
      $element['entities']['#attached']['library'] = [];
    }

    $element['entities']['#theme'] = 'entity_reference_layout_widget';
    $entities = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'entities',
    ]);
    $element['entities']['#widget'] = 'entity_reference_layout_widget';

    $handler_settings = $items->getSetting('handler_settings');
    $layout_bundles = $element['entities']['#layout_bundles'] = $handler_settings['layout_bundles'];
    $this->allowedLayouts = isset($handler_settings['allowed_layouts']) ? $handler_settings['allowed_layouts'] : [];

    // Attach libraries from all available layouts.
    foreach ($this->allowedLayouts as $layouts) {
      foreach (array_keys($layouts) as $layout_id) {
        $layout_instance = $this->layoutPluginManager->createInstance($layout_id);
        if ($plugin_definition = $layout_instance->getPluginDefinition()) {
          $library = $plugin_definition->getLibrary();
          if (!empty($library)) {
            $element['entities']['#attached']['library'][] = $library;
          }
        }
      }
    }

    // Load paragraph type icons and libraries.
    $target_type = $items->getSetting('target_type');
    $bundles = $handler_settings['target_bundles'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);
    $storage = $this->entityTypeManager->getStorage($target_type);
    foreach ($bundles as $bundle) {

      $values = [];
      if ($bundle_key = $entity_type->getKey('bundle')) {
        $values[$bundle_key] = $bundle;
      }
      $entity = $storage->create($values);

      // Render an empty entity to get the attached libraries.
      // This ensure CSS/JS is loaded when entities are created via AJAX.
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $preview = $view_builder->view($entity);
      $this->renderer->render($preview);
      $element['entities']['#attached']['library'] += $preview['#attached']['library'];

      // Get the icon and pass to Javascript.
      if (method_exists($entity, 'getParagraphType')) {
        if ($icon = $entity->getParagraphType()->getIconFile()) {
          $path = $icon->url();
          $element['entities']['#attached']['drupalSettings']['erlIcons']['icon_' . $bundle] = $path;
        }
      }
    }

    $field_name = $this->fieldDefinition->getName();
    $element['#field_name'] = $field_name;

    if (isset($element['actions'])) {

      $element['actions']['#attributes']['class'][] = 'erl-field-actions';
      $element['actions']['bundle']['#attributes']['class'][] = 'hidden';
      $element['actions']['ief_add']['#attributes']['style'][] = 'display:none;';

      if (!count($entities)) {
        $element['actions']['#attributes']['class'][] = 'empty';
      }
      $nested_bundles = ['Layout' => [], 'Content' => []];
      foreach ($element['actions']['bundle']['#options'] as $key => $val) {
        $bundle_type = in_array($key, $layout_bundles) ? 'Layout' : 'Content';
        $nested_bundles[$bundle_type][$key] = $val;
      }
      $element['actions']['bundle']['#options'] = $nested_bundles;
      $element['actions']['region'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'class' => ['erl-new-item-region', 'hidden'],
        ],
      ];
      $element['actions']['delta'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'class' => ['erl-new-item-delta', 'hidden'],
        ],
      ];
    }

    if ($this->getSetting('full_screen_editing')) {
      $element['entities']['#attached']['library'][] = 'entity_reference_layout/full_screen_editing';
    }

    foreach ($entities as $delta => $value) {

      $layout_properties = $value['layout_properties'];

      // We set #layout_parent to instruct the theme function where to position
      // new entities that have not been saved to the db yet but need to be
      // represented on the screen, in the right place.
      if (isset($layout_properties['parent']) && is_numeric($layout_properties['parent'])) {
        $element['entities'][$delta]['#layout_parent'] = $layout_properties['parent'];
      }

      /* @var \Drupal\core\Entity\EntityInterface $entity */
      $entity = $value['entity'];
      $form_item =& $element['entities'][$delta];
      $form_item['#layout_properties'] = $layout_properties;

      // Render the paragraph on the edit form.
      // @todo Allow admins to configure which view mode should be used.
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $preview = $view_builder->view($entity);
      $rendered_preview = $this->renderer->render($preview);
      $form_item['prefix'] = [
        '#markup' => $rendered_preview,
        '#weight' => -100,
      ];

      if (!empty($form_item['form'])) {
        if ($value['form'] == 'remove') {
          try {
            $form_item['form']['#attributes']['class'][] = 'erl-remove-form';
            /* @var \Drupal\core\Entity\EntityInterface $form_item_entity */
            $form_item_entity = $form_item['#entity'];
            $bundle_type_id = $form_item_entity->getEntityType()
              ->getBundleEntityType();
            $bundle_label = $this->entityTypeManager->getStorage($bundle_type_id)
              ->load($form_item_entity->bundle())
              ->label();
            if ($bundle_label) {
              $form_title = $this->t('Remove @bundle_label', ['@bundle_label' => $bundle_label]);
              $form_item['form']['ief_title'] = [
                '#markup' => '<h2 class="ief-title visually-hidden">' . $form_title . '</h2>',
                '#weight' => -1000,
              ];
              $form_item['form']['message']['#markup'] = $this->t('Are you sure you want to remove this %bundle?', ['%bundle' => $bundle_label]);
              $form_item['form']['message']['#attributes']['class'] = ['erl-remove-message'];
            }
          }
          catch (\Exception $e) {
            watchdog_exception('entity_reference_layout', $e);
          }
        }
      }

      // Edit form is open for this item.
      if (!empty($form_item['form']['inline_entity_form'])) {
        // Allow module to alter forms.
        $form_item['form']['#process'][] = 'entity_reference_layout_alter_form';
        $form_item['form']['#theme'] = 'entity_reference_layout_ief';
        $form_item['form']['inline_entity_form']['#process'][] = [$this, 'iefFormTitle'];
        $form_item['form']['inline_entity_form']['#process'][] = [get_class($this), 'alterEntityFormActions'];

        // This is a layout item so add the layout options form.
        if (in_array($layout_properties['entity_bundle'], $layout_bundles)) {
          $form_item['form']['inline_entity_form']['#layout_properties'] = $form_state->get([
            'inline_entity_form',
            $this->getIefId(),
            'entities',
            $delta,
            'layout_properties',
          ]);
          $form_item['form']['inline_entity_form']['#process'][] = [$this, 'layoutOptionsForm'];
        }
        $form_item['form']['inline_entity_form']['#erl_form'] = TRUE;
      }

      // Adds layout property fields (region, delta, etc.).
      $form_item['#process'][] = [$this, 'layoutPropertiesForm'];

      // Hide delta without relying on javascript.
      $form_item['delta']['#attributes']['class'][] = 'hidden';

      // Set layout info for rendering in theme function.
      if (in_array($entity->bundle(), $layout_bundles) && !empty($layout_properties['layout'])) {
        $form_item['#layout'] = $layout_properties['layout'];
      }
      elseif (!empty($layout_properties['region'])) {
        $form_item['#region'] = $layout_properties['region'];
      }

      // Set layout options for use in rendering form.
      if (isset($value['layout_properties']['options'])) {
        $form_item['#layout_options'] = $value['layout_properties']['options'];
      }

      // Set layout plugin config for use in rendering form.
      if (isset($value['layout_properties']['config'])) {
        $form_item['#layout_config'] = !empty($value['layout_properties']['config']) ? $value['layout_properties']['config'] : [];
      }
      else {
        $form_item['#layout_config'] = [];
      }

    }

    // Add layout options for inline entity form:
    if (isset($element['form']['inline_entity_form'])) {

      // Allow module to alter form.
      $element['form']['#theme'] = 'entity_reference_layout_ief';
      $element['form']['#process'][] = 'entity_reference_layout_alter_form';
      $element['form']['#attributes']['class'][] = 'erl-add-entity-form';
      $element['form']['inline_entity_form']['#erl_form'] = TRUE;
      $element['form']['inline_entity_form']['#process'][] = [$this, 'iefFormTitle'];
      $element['form']['inline_entity_form']['#process'][] = [get_class($this), 'alterEntityFormActions'];

      // Using (and sanitizing) raw input because $form_state values
      // are not dependable at this point.
      $input = $form_state->getUserInput();
      $default_properties = [
        'parent' => 10000,
        'entity_bundle' => $element['form']['inline_entity_form']['#bundle'],
        'is_new' => TRUE,
        'reset' => TRUE,
      ];

      // Use form input for layout name if the user switched layouts.
      $parents = array_merge($element['form']['inline_entity_form']['#parents'], ['layout_selection', 'layout']);
      $layout_name = preg_replace('/[^A-Za-z0-9\-\_]/', '', NestedArray::getValue($input, $parents));
      if ($layout_name) {
        $default_properties['layout'] = $layout_name;
      }

      $default_properties['is_layout_bundle'] = in_array($default_properties['entity_bundle'], $layout_bundles);
      if (isset($input[$field_name]['actions']['region'])) {
        $default_properties['region'] = preg_replace('/[^A-Za-z0-9\-\_]/', '', $input[$field_name]['actions']['region']);
      }
      if (isset($input[$field_name]['actions']['delta'])) {
        $default_properties['parent'] = intval($input[$field_name]['actions']['delta']);
      }

      $element['form']['inline_entity_form']['#layout_properties'] = $default_properties;
      if (in_array($default_properties['entity_bundle'], $layout_bundles)) {
        $element['form']['inline_entity_form']['#process'][] = [$this, 'layoutOptionsForm'];
      }

      // Adds layout property fields (region, delta, etc.).
      $element['form']['inline_entity_form']['#process'][] = [$this, 'layoutPropertiesForm'];
    }

    $element['#attributes'] = [
      'class' => ['erl-field'],
    ];

    $element['#attached']['library'][] = 'core/drupal.dialog';

    return $element;
  }

  /**
   * Manipulate form actions form element.
   */
  public static function alterEntityFormActions($element) {
    $element['actions']['#weight'] = 10000;
    $element['actions']['#attributes']['class'][] = 'erl-form-actions';

    if (!empty($element['#bundle'])) {
      try {
        /* @var \Drupal\core\Entity\EntityInterface $element_entity */
        $element_entity = $element['#entity'];
        $bundle_type_id = $element_entity->getEntityType()
          ->getBundleEntityType();
        $bundle_label = \Drupal::entityTypeManager()
          ->getStorage($bundle_type_id)
          ->load($element['#bundle'])
          ->label();

        if ($element['#op'] == 'add') {
          $save_label = t('Create @type_singular', ['@type_singular' => $bundle_label]);
        }
        elseif ($element['#op'] == 'duplicate') {
          $save_label = t('Duplicate @type_singular', ['@type_singular' => $bundle_label]);
        }
        else {
          $save_label = t('Update @type_singular', ['@type_singular' => $bundle_label]);
        }
        $element['actions']['ief_' . $element['#op'] . '_save']['#value'] = $save_label;
      }
      catch (\Exception $e) {
        watchdog_exception('entity_reference_layout', $e);
      }
    }
    return $element;
  }

  /**
   * Add a title element inline entity forms.
   */
  public function iefFormTitle(array $element, FormStateInterface $form_state) {
    $entity_type = $element['#entity_type'];
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    $bundle_id = $element['#bundle'];
    $bundle_label = $bundle_info[$bundle_id]['label'];
    if ($element['#op'] == 'add') {
      $form_title = $this->t('Add new @bundle', ['@bundle' => $bundle_label]);
    }
    if ($element['#op'] == 'edit') {
      $form_title = $this->t('Edit @bundle', ['@bundle' => $bundle_label]);
    }
    // Hidden element, used in js.
    $element['ief_title'] = [
      '#markup' => '<h2 class="ief-title visually-hidden">' . $form_title . '</h2>',
      '#weight' => -1000,
    ];
    return $element;
  }

  /**
   * Build layout properties form.
   *
   * @todo: Use configuration options from layout plugins.
   *   see https://www.drupal.org/project/entity_reference_layout/issues/3038533
   */
  public function layoutPropertiesForm($element, $form_state) {

    $default_properties = $element['#layout_properties'];
    $element['layout_properties'] = [
      '#type' => 'container',
      '#weight' => -1000,
    ];
    $element['layout_properties']['#attributes'] = ['class' => ['hidden']];
    $element['layout_properties']['layout'] = [
      '#type' => 'hidden',
      // Use value because this doesn't change from this form element.
      '#value' => isset($default_properties['layout']) ? $default_properties['layout'] : '',
      '#attributes' => [
        'class' => [
          'erl-layout-select',
          'erl-layout',
          'erl-layout-' . (!empty($default_properties['layout']) ? $default_properties['layout'] : ''),
        ],
      ],
    ];
    $element['layout_properties']['region'] = [
      '#type' => 'hidden',
      '#default_value' => !empty($default_properties['region']) ? $default_properties['region'] : '',
      '#attributes' => [
        'class' => [
          'erl-region-select',
          'erl-region',
          'erl-region-' . (!empty($default_properties['region']) ? $default_properties['region'] : ''),
        ],
      ],
    ];
    $element['layout_properties']['entity_bundle'] = [
      '#type' => 'value',
      '#value' => $default_properties['entity_bundle'],
    ];
    $element['layout_properties']['parent'] = [
      '#type' => 'hidden',
      '#default_value' => intval($default_properties['parent']),
      '#attributes' => [
        'class' => ['parent-delta'],
      ],
    ];

    return $element;
  }

  /**
   * Add theme wrappers to layout selection radios.
   */
  public static function processLayoutOptions($element) {
    foreach (Element::children($element) as $radio_item) {
      $element[$radio_item]['#theme_wrappers'][] = 'entity_reference_layout_radio';
    }
    return $element;
  }

  /**
   *
   */
  public function radioThemeWrapper($element) {
    $element['#attributes']['class'][] = 'test-class';
    return \Drupal::service('renderer')->render($element);
  }

  /**
   * Build a layout options form.
   *
   * @todo: Use form from layout plugins.
   *   See https://www.drupal.org/project/entity_reference_layout/issues/3038533
   */
  public function layoutOptionsForm($element, $form_state) {

    $defaults = $element['#layout_properties'];
    $layout_options = [];
    foreach ($this->allowedLayouts as $layout_group) {
      foreach ($layout_group as $layout_id => $layout_name) {
        $layout_options[$layout_id] = $layout_name;
      }
    }

    $default_layout = !empty($defaults['layout']) ? $defaults['layout'] : key($layout_options);

    $element['layout_selection'] = [
      '#type' => 'container',
    ];
    $element['layout_selection']['layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select a layout:'),
      '#description' => $this->t('Choose a layout for this section.'),
      '#options' => $layout_options,
      '#default_value' => $default_layout,
      '#attributes' => [
        'class' => ['erl-layout', 'erl-layout-section-select'],
      ],
      '#required' => TRUE,
      '#after_build' => [[get_class($this), 'processLayoutOptions']],
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'buildLayoutConfigurationFormAjaxCallback'],
        'wrapper' => 'layout-config',
        'trigger_as' => ['name' => 'update_layout'],
      ],
    ];
    // Add update button and submit handler to switch between layouts.
    $element['layout_selection']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#name' => 'update_layout',
      '#submit' => [
        [$this, 'updateLayoutOptionsForm'],
      ],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'buildLayoutConfigurationFormAjaxCallback'],
        'wrapper' => 'layout-config',
      ],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
    ];
    if (isset($defaults['is_new'])) {
      $element['layout_selection']['update']['#submit'][] = 'inline_entity_form_open_form';
      $element['layout_selection']['update']['#ief_form'] = 'add';
      $element['layout_selection']['update']['#is_new'] = TRUE;
    }
    else {
      $element['layout_selection']['update']['#submit'][] = 'inline_entity_form_open_row_form';
      $element['layout_selection']['update']['#ief_row_delta'] = $element['#ief_row_delta'];
      $element['layout_selection']['update']['#ief_row_form'] = 'edit';
      $element['layout_selection']['update']['#is_new'] = FALSE;
    }

    // Add config form.
    $element['layout_config'] = [
      '#prefix' => '<div id="layout-config">',
      '#suffix' => '</div>',
      '#type' => 'details',
      '#title' => $this->t('Layout Plugin Configuration'),
      '#access' => $this->currentUser->hasPermission('edit entity reference layout plugin config'),
      'config' => [],
    ];

    // A layout has been selected, try to render it's plugin form.
    if ($default_layout) {
      $plugin_config = isset($defaults['config']) ? $defaults['config'] : [];
      /** @var \Drupal\Core\Layout\LayoutInterface $layout_instance */
      $layout_instance = $this->layoutPluginManager->createInstance($default_layout, $plugin_config);
      $plugin_form = $this->getLayoutPluginForm($layout_instance);
      if ($plugin_form) {
        // @todo Decide whether or not to use subformstate.
        $element['layout_config']['config'] = $plugin_form->buildConfigurationForm([], $form_state);
      }
      else {
        $element['layout_config']['#attributes']['class'][] = 'visually-hidden';
      }
    }

    // Other layout options.
    $element['layout_options']['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional Layout Options'),
      '#description' => $this->t('Classes will be applied to the container for this field item.'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('adjust entity reference layout options'),
    ];
    $element['layout_options']['options']['container_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Classes for Layout Container'),
      '#description' => $this->t('Classes will be applied to the container for this field item.'),
      '#size' => 50,
      '#default_value' => !empty($defaults['options']['options']['container_classes']) ? $defaults['options']['options']['container_classes'] : '',
      '#placeholder' => $this->t('CSS Classes'),
    ];
    $element['layout_options']['options']['bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color for Layout Container'),
      '#description' => $this->t('Background will be applied to the layout container.'),
      '#size' => 10,
      '#default_value' => !empty($defaults['options']['options']['bg_color']) ? $defaults['options']['options']['bg_color'] : '',
      '#placeholder' => $this->t('Hex Code'),
    ];
    // Hide the options form if "Show options form" is not selected in settings.
    if (!empty($plugin_form) && $this->getSetting('always_show_options_form') != TRUE) {
      $element['layout_options']['options']['#access'] = FALSE;
    }

    $event = new ErlPropertiesFormEvent($element, $defaults);
    $this->eventDispatcher->dispatch(ErlPropertiesFormEvent::EVENT_NAME, $event);

    return $element;
  }

  /**
   * Submit callback - saves the selected layout in $form_state.
   */
  public function updateLayoutOptionsForm($form, $form_state) {
    $button = $form_state->getTriggeringElement();
    $button_parents = array_merge(array_splice($button['#parents'], 0, -1), ['layout']);
    $input = $form_state->getUserInput();
    $layout = NestedArray::getValue($input, $button_parents);

    if ($button['#is_new']) {
      $layout_properties_parents = [
        'inline_entity_form',
        $this->getIefId(),
        'default_layout_properties',
        'layout',
      ];
    }
    else {
      $layout_properties_parents = [
        'inline_entity_form',
        $this->getIefId(),
        'entities',
        $button['#ief_row_delta'],
        'layout_properties',
        'layout',
      ];
    }
    $form_state->set($layout_properties_parents, $layout);
  }

  /**
   * Render a layout plugin form, if it exists.
   */
  public function buildLayoutConfigurationFormAjaxCallback(array &$form, FormStateInterface &$form_state) {
    $element = $form_state->getTriggeringElement();
    $parents = array_merge(array_splice($element['#array_parents'], 0, -2), ['layout_config']);
    $form_item = NestedArray::getValue($form, $parents);

    if (empty($form_item)) {
      $form_item = [
        '#prefix' => '<div id="layout-config">',
        '#suffix' => '</div>',
      ];
    }
    return $form_item;
  }

  /**
   * Field instance settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // @FIXME
    // Allow existing doesn't work at the moment (it breaks all the widget).
    // It is thus disabled at the moment.
    $form['allow_existing']['#disabled'] = TRUE;
    $form['allow_existing']['#description'] = $this->t('This feature is not working at the moment (disabled)');

    // @FIXME
    // Allow duplicate doesn't work at the moment (unpredictable results).
    // correct behavior. It is thus disabled at the moment.
    $form['allow_duplicate']['#disabled'] = TRUE;
    $form['allow_duplicate']['#description'] = $this->t('This feature is not working at the moment (disabled)');

    $form['full_screen_editing'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('full_screen_editing'),
      '#title' => $this->t('Full screen editing'),
      '#description' => $this->t('Show node edit form full-screen to make room for layout builder'),
    ];

    $form['always_show_options_form'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('always_show_options_form'),
      '#title' => $this->t('Always show layout options form'),
      '#description' => $this->t('Show options for additional classes and background color when adding or editing layouts, even if a layout plugin form exists. The preferred method is to rely on Layout Plugin configuration forms.'),
    ];
    return $form;
  }

  /**
   * Default settings for widget.
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'full_screen_editing' => FALSE,
      'always_show_options_form' => FALSE,
    ];

    return $defaults;
  }

  /**
   * Massages the form values into the format expected for field values.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();
    $field_value = $form_state->getValue($field_name);
    $entities = $form_state->get([
      'inline_entity_form',
      $this->getIefId(),
      'entities',
    ]);

    foreach ($values as $delta => $value) {
      if (isset($field_value['entities'][$delta])) {
        $values[$delta]['region'] = !empty($field_value['entities'][$delta]['layout_properties']['region']) ? $field_value['entities'][$delta]['layout_properties']['region'] : '';
        $values[$delta]['layout'] = !empty($field_value['entities'][$delta]['layout_properties']['layout']) ? $field_value['entities'][$delta]['layout_properties']['layout'] : '';
      }
      // Configuration provided by layout plugin.
      if (isset($entities[$delta]['layout_properties']['config'])) {
        $values[$delta]['options'] = $entities[$delta]['layout_properties']['options'];
        $values[$delta]['config'] = $entities[$delta]['layout_properties']['config'];
      }
    }
    return $values;
  }

  /**
   * Retrieves the plugin form for a given layout.
   *
   * @param \Drupal\Core\Layout\LayoutInterface $layout
   *   The layout plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the layout.
   */
  protected function getLayoutPluginForm(LayoutInterface $layout) {
    if ($layout instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($layout, 'configure');
    }

    if ($layout instanceof PluginFormInterface) {
      return $layout;
    }

    return FALSE;
  }

}
