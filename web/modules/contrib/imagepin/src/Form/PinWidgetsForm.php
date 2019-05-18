<?php

namespace Drupal\imagepin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Component\Serialization\Json;
use Drupal\imagepin\Plugin\WidgetManager;
use Drupal\imagepin\WidgetRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delivers a form for pinning widgets on an image.
 *
 * The image is being displayed inside certain view modes of an entity.
 * Therefore, the form enables a user to setup the pins per view mode.
 */
class PinWidgetsForm extends FormBase {

  /**
   * The EntityTypeManagerInterface instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EntityDisplayRepositoryInterface instance.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * The WidgetManager instance.
   *
   * @var \Drupal\imagepin\Plugin\WidgetManager
   */
  protected $widgetManager;

  /**
   * The WidgetRepository instance.
   *
   * @var \Drupal\imagepin\WidgetRepository
   */
  protected $widgetRepository;

  /**
   * The FormBuilder instance.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The JSON serializer.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $jsonSerializer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('imagepin.widget_manager'),
      $container->get('imagepin.widget_repository'),
      $container->get('form_builder'),
      $container->get('serialization.json'),
      $container->get('module_handler')
    );
  }

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface instance.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository
   *   The EntityDisplayRepositoryInterface instance.
   * @param \Drupal\imagepin\Plugin\WidgetManager $widget_manager
   *   The WidgetManager instance.
   * @param \Drupal\imagepin\WidgetRepository $widget_repository
   *   The WidgetRepository instance.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The FormBuiderInterface instance.
   * @param \Drupal\Component\Serialization\Json $json_serializer
   *   The JSON serializer.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $display_repository, WidgetManager $widget_manager, WidgetRepository $widget_repository, FormBuilderInterface $form_builder, Json $json_serializer, ModuleHandler $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->displayRepository = $display_repository;
    $this->widgetManager = $widget_manager;
    $this->widgetRepository = $widget_repository;
    $this->formBuilder = $form_builder;
    $this->jsonSerializer = $json_serializer;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'imagepin_widgets_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];
    $belonging = $form_state->getValue('belonging', []);
    if (empty($belonging) && !empty($args)) {
      $belonging = [
        'image_fid' => $args[0],
        'field_name' => $args[1],
        'entity_type' => $args[2],
        'bundle' => $args[3],
        'language' => $args[4],
        'entity_id' => is_numeric($args[5]) ? $args[5] : NULL,
      ];
      $form_state->setValue('belonging', $belonging);
    }
    if (empty($belonging)) {
      throw new \Exception('Invalid invoke of PinWidgetsForm::buildForm(), missing belonging arguments.');
    }

    $image = $this->entityTypeManager->getStorage('file')->load($belonging['image_fid']);

    $enabled_view_modes = $this->displayRepository
      ->getViewModeOptionsByBundle($belonging['entity_type'], $belonging['bundle']);

    if (empty($enabled_view_modes)) {
      return $form;
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'imagepin/admin';

    $form['belonging'] = [
      'image_fid' => [
        '#type' => 'value',
        '#value' => $belonging['image_fid'],
      ],
      'field_name' => [
        '#type' => 'value',
        '#value' => $belonging['field_name'],
      ],
      'entity_type' => [
        '#type' => 'value',
        '#value' => $belonging['entity_type'],
      ],
      'bundle' => [
        '#type' => 'value',
        '#value' => $belonging['bundle'],
      ],
      'entity_id' => [
        '#type' => 'value',
        '#value' => $belonging['entity_id'],
      ],
      'language' => [
        '#type' => 'value',
        '#value' => $belonging['language'],
      ],
    ];

    // Just in case vertical_tabs are needed,
    // the key must be in order before the pinable display groups.
    $form['display_tabs'] = [];

    $view_storage = $this->entityTypeManager->getStorage('entity_view_display');

    $view_modes_pinable = [];
    foreach ($enabled_view_modes as $view_mode => $view_mode_label) {
      $content = $view_storage->load($belonging['entity_type'] . '.' . $belonging['bundle'] . '.' . $view_mode)->get('content');
      $field_name = $belonging['field_name'];
      if (empty($content[$field_name]['third_party_settings']['imagepin']['pinable'])
      || empty($content[$field_name]['third_party_settings']['imagepin']['image_style'])) {
        continue;
      }

      $imagepin_settings = $content[$field_name]['third_party_settings']['imagepin'];
      $view_modes_pinable[$view_mode] = $view_mode_label;
      $form['display'][$view_mode] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'edit-' . $view_mode],
      ];
      $form['display'][$view_mode]['image'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Image'),
        '#attributes' => ['data-view-mode' => $view_mode, 'class' => ['editable-image-container']],
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      ];
      $form['display'][$view_mode]['image']['element'] = [
        '#theme' => 'image_style',
        '#style_name' => $imagepin_settings['image_style'],
        '#attributes' => ['class' => ['imagepin-image'], 'data-view-mode' => $view_mode],
        '#uri' => $image->getFileUri(),
      ];

      $form['display'][$view_mode]['image']['positions'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'imagepin-positions-' . $view_mode,
          'class' => ['imagepin-positions'],
          'data-view-mode' => $view_mode,
        ],
        'settings' => [
          '#type' => 'hidden',
          '#default_value' => '{}',
          '#attributes' => ['class' => ['imagepin-positions-input'], 'data-view-mode' => $view_mode],
        ],
        'settings_save' => [
          '#type' => 'submit',
          '#name' => 'save_positions_' . $view_mode,
          '#value' => $this->t('Save these positions'),
          '#attributes' => ['class' => ['imagepin-positions-save'], 'data-view-mode' => $view_mode],
          '#ajax' => [
            'callback' => [$this, 'savePositions'],
            'wrapper' => 'imagepin-positions-' . $view_mode,
            'effect' => 'fade',
            'method' => 'replaceWith',
            'progress' => [
              'type' => 'throbber',
              'message' => '',
            ],
          ],
        ],
      ];

      $form['display'][$view_mode]['widgets'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Widgets'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#attributes' => ['id' => 'edit-' . $view_mode . '-widgets'],
      ];

      $this->buildAvailableWidgetElements($form, $form_state, $view_mode);

      $form['display'][$view_mode]['widgets']['new'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('New'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        'add' => [
          '#type' => 'submit',
          '#name' => 'add_new_widget_' . $view_mode,
          '#value' => $this->t('Add'),
          '#weight' => 100,
          '#ajax' => [
            'callback' => [$this, 'addNewWidget'],
            'wrapper' => 'edit-' . $view_mode . '-widgets',
            'effect' => 'fade',
            'method' => 'replaceWith',
            'progress' => [
              'type' => 'throbber',
              'message' => '',
            ],
          ],
        ],
      ];

      $widget_definitions = $this->widgetManager->getDefinitions();
      foreach ($this->widgetManager->getDefinitions() as $id => $definition) {
        $widget_definitions[$id] = $definition['label'];
      }
      $default_plugin = key($widget_definitions);

      // Allow other modules to alter the list of allowed plugins per view mode.
      $context = [
        'view_mode' => $view_mode,
        'belonging_entity_type' => $belonging['entity_type'],
      ];
      $this->moduleHandler->alter('allowed_widget_plugins', $widget_definitions, $default_plugin, $context);

      $display = $form_state->getValue('display', []);
      if (!empty($display[$view_mode]['widgets']['new']['plugin'])) {
        $default_plugin = $display[$view_mode]['widgets']['new']['plugin'];
      }
      if (count($widget_definitions) > 1) {
        $form['display'][$view_mode]['widgets']['new']['plugin'] = [
          '#type' => 'select',
          '#options' => $widget_definitions,
          '#default_value' => $default_plugin,
          '#title' => $this->t('Type'),
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '</div>',
          '#weight' => 10,
          '#ajax' => [
            'callback' => [$this, 'switchWidgetElement'],
            'wrapper' => 'new-widget-' . $view_mode,
            'effect' => 'fade',
            'method' => 'replaceWith',
            'progress' => [
              'type' => 'throbber',
              'message' => '',
            ],
          ],
        ];
      }
      else {
        $form['display'][$view_mode]['widgets']['new']['plugin'] = [
          '#type' => 'value',
          '#value' => $default_plugin,
        ];
      }
      $this->switchWidgetElement($form, $form_state, $default_plugin, $view_mode);
    }

    // Move multiple editable view modes into vertical tabs.
    if (count($view_modes_pinable) > 1) {
      $form['display_tabs'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-' . key($view_modes_pinable),
      ];
      foreach ($view_modes_pinable as $mode => $label) {
        $form['display'][$mode]['#type'] = 'details';
        $form['display'][$mode]['#title'] = $label;
        $form['display'][$mode]['#group'] = 'display_tabs';
      }
    }

    return $form;
  }

  /**
   * Switches the element type for a new widget.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $plugin_id
   *   (Optional) The widget plugin id as string.
   * @param string $view_mode
   *   (Optional) The view mode id as string.
   */
  public function switchWidgetElement(array &$form, FormStateInterface $form_state, $plugin_id = NULL, $view_mode = NULL) {
    if (!isset($view_mode)) {
      $trigger = $form_state->getTriggeringElement();
      $view_mode = $trigger['#parents'][1];
    }
    $change = FALSE;
    if (!isset($plugin_id) || $plugin_id instanceof Request) {
      $plugin_id = $form_state->getValue('display')[$view_mode]['widgets']['new']['plugin'];
      $change = TRUE;
    }

    $widget = $this->widgetManager->createInstance($plugin_id);
    $form['display'][$view_mode]['widgets']['new']['widget'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'new-widget-' . $view_mode],
      '#weight' => 50,
      $plugin_id => $widget->formNewElement($form, $form_state),
    ];
    if ($change) {
      // Form parts have changed, which requires a rebuild.
      $form_state->setRebuild();
      $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    }

    return $form['display'][$view_mode]['widgets']['new']['widget'];
  }

  /**
   * Submit handler for adding a new widget.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (Optional) The current request object, usually delivered by the AJAX API.
   */
  public function addNewWidget(array &$form, FormStateInterface $form_state, Request $request = NULL) {
    $trigger = $form_state->getTriggeringElement();
    $view_mode = $trigger['#parents'][1];
    $plugin_id = $form_state->getValue('display')[$view_mode]['widgets']['new']['plugin'];
    $widget = $this->widgetManager->createInstance($plugin_id);

    $display = $form_state->getValue('display');
    $value = $display[$view_mode]['widgets']['new']['widget'][$plugin_id];
    $belonging = ['view_mode' => $view_mode] + $form_state->getValue('belonging');
    $this->widgetRepository->save($widget, $value, $belonging);

    if ($request instanceof Request) {
      $form_state->setRebuild();
      $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    }

    return $form['display'][$view_mode]['widgets'];
  }

  /**
   * Builds the form element for available widgets.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $view_mode
   *   The view mode as string.
   */
  public function buildAvailableWidgetElements(array &$form, FormStateInterface $form_state, $view_mode) {
    $belonging = $form_state->getValue('belonging');
    $belonging['view_mode'] = $view_mode;
    $available = $this->widgetRepository->loadBelonging($belonging);
    $form['display'][$view_mode]['widgets']['available'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available'),
      '#description' => empty($available) ? $this->t('Add a widget below to drag it on the image.') : $this->t('Click on a pin and drag it to the desired position on the image.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => ['id' => 'available-' . $view_mode],
    ];
    $elements = [];
    foreach ($available as $key => $widget) {
      $pin = [
        '#type' => 'container',
        '#attributes' => ['class' => ['imagepin'], 'data-imagepin-key' => $key, 'data-view-mode' => $view_mode],
        'content' => $widget['plugin']->viewPinContent($widget['value']),
      ];
      if ($position = $widget['plugin']->getPosition($widget['value'])) {
        $pin['#attributes']['data-position-default'] =
          $this->jsonSerializer->encode($position);
      }

      $elements[$key] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'available-widget-' . $key, 'class' => ['container-inline']],
        '#weight' => $key * 10,
        'elements' => [
          '#type' => 'table',
          '#attributes' => ['class' => 'imagepin-widget-element-form-table'],
          0 => [
            'pin' => [
              '#weight' => 10,
              'element' => [$pin],
            ],
            'preview' => [
              '#weight' => 20,
              'element' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['imagepin-widget']],
                'preview' => [
                  '#type' => 'container',
                  '#attributes' => ['class' => ['imagepin-widget-preview']],
                  'widget' => $widget['plugin']->previewContent($widget['value']),
                ],
              ],
            ],
            'actions' => [
              '#weight' => 100,
              'remove' => [
                '#type' => 'submit',
                '#name' => 'remove_widget_' . $key,
                '#value' => $this->t('Remove'),
                '#attributes' => ['class' => ['imagepin-remove'], 'data-imagepin-key' => $key],
                '#ajax' => [
                  'callback' => [$this, 'removeWidget'],
                  'wrapper' => 'available-' . $view_mode,
                  'effect' => 'fade',
                  'method' => 'replaceWith',
                  'progress' => [
                    'type' => 'throbber',
                    'message' => '',
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }
    $form['display'][$view_mode]['widgets']['available']['elements'] = $elements;
    return $form['display'][$view_mode]['widgets']['available']['elements'];
  }

  /**
   * Submit handler for removing an existing widget.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (Optional) The current request object, usually delivered by the AJAX API.
   */
  public function removeWidget(array &$form, FormStateInterface $form_state, Request $request = NULL) {
    $trigger = $form_state->getTriggeringElement();
    $view_mode = $trigger['#parents'][1];
    $widget_key = $trigger['#parents'][5];

    unset($form['display'][$view_mode]['widgets']['available']['elements'][$widget_key]);
    $this->widgetRepository->delete($widget_key);

    if ($request) {
      $form_state->setRebuild();
      $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    }

    return $form['display'][$view_mode]['widgets']['available'];
  }

  /**
   * Submit handler for saving the widget positions.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (Optional) The current request object, usually delivered by the AJAX API.
   */
  public function savePositions(array &$form, FormStateInterface $form_state, Request $request = NULL) {
    $trigger = $form_state->getTriggeringElement();
    $view_mode = $trigger['#parents'][1];

    $positions = $form_state->getValue(['display', $view_mode, 'image', 'positions', 'settings']);
    $positions = $this->jsonSerializer->decode($positions);
    $belonging = $form_state->getValue('belonging');
    $belonging['view_mode'] = $view_mode;

    $widgets = $this->widgetRepository->loadBelonging($belonging);
    foreach ($widgets as $widget) {
      $key = $widget['key'];
      if (!empty($positions[$key])) {
        $widget['plugin']->setPosition($widget['value'], $positions[$key]);
        $this->widgetRepository->save($widget['plugin'], $widget['value'], $belonging, $key);
      }
    }

    return $form['display'][$view_mode]['image']['positions'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
