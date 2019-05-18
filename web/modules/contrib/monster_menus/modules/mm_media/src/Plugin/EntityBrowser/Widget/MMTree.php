<?php

namespace Drupal\mm_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Element\MMCatlist;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "mm_tree",
 *   label = @Translation("MM Tree"),
 *   description = @Translation("Uses a tree browser to select media."),
 *   auto_select = FALSE
 * )
 */
class MMTree extends WidgetBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Upload constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mm_list_popup_start' => 1,
      'mm_list_popup_root' => 1,
      'mm_list' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $field_cardinality = $form_state->get(['entity_browser', 'validators', 'cardinality', 'cardinality']);
    $form['img_mmtids'] = array(
      '#title' => $this->t('Media:'),
      '#type' => 'mm_medialist',
      '#default_value' => $this->configuration['mm_list'] ?: ['' => $this->t('(choose a media item)')],
      '#mm_list_popup_start' => mm_ui_mmlist_key0($this->configuration['mm_list_popup_start']),
      '#mm_list_popup_root' => mm_ui_mmlist_key0($this->configuration['mm_list_popup_root']),
      '#mm_list_selectable' => Constants::MM_PERMS_READ,
      '#description' => $this->t('Add one or more media items.'),
    );
    if ($field_cardinality == 1) {
      $form['img_mmtids']['#mm_list_min'] = $form['img_mmtids']['#mm_list_max'] = 1;
      unset($form['img_mmtids']['#description']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $files = [];
    foreach (array_keys($form_state->getValue(['img_mmtids'], [])) as $spec) {
      $parts = explode('/', $spec);
      if (isset($parts[2])) {
        $files[] = $this->entityTypeManager->getStorage('media')->load($parts[2]);
      }
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $this->selectEntities($this->prepareEntities($form, $form_state), $form_state);
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
//  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
//    // We propagated entities to the other parts of the system. We can now remove
//    // them from our values.
//    $form_state->setValueForElement($element['upload']['fids'], '');
//    NestedArray::setValue($form_state->getUserInput(), $element['upload']['fids']['#parents'], '');
//  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['mm_list_popup_root'] = [
      '#type' => 'mm_catlist',
      '#title' => $this->t('Topmost location in tree shown'),
      '#default_value' => $this->configuration['mm_list_popup_root'],
      '#value_callback' => [static::class, 'configValueCallback'],
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_popup_root' => 0,
      '#mm_list_selectable' => Constants::MM_PERMS_READ,
      '#required' => TRUE,
    ];
    $form['mm_list_popup_start'] = [
      '#type' => 'mm_catlist',
      '#title' => $this->t('Default location in tree'),
      '#default_value' => $this->configuration['mm_list_popup_start'],
      '#value_callback' => [static::class, 'configValueCallback'],
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_popup_root' => 0,
      '#mm_list_selectable' => Constants::MM_PERMS_READ,
      '#element_validate' => [[static::class, 'validateStart']],
      '#required' => TRUE,
    ];

    return $form;
  }

  public static function configValueCallback($element, $input, FormStateInterface $form_state) {
    if ($input === FALSE && isset($element['#default_value'])) {
      $input = $element['#default_value'];
    }
    if ($input) {
      if (is_numeric($input)) {
        return [$input => mm_content_get_name($input)];
      }
      $temp_element = $element + ['#value' => $input];
      MMCatlist::process($temp_element, $form_state);
      return $temp_element['#value'];
    }
    return '';
  }

  /**
   * Ensure that the start location has the root location as a parent.
   */
  public static function validateStart(array $element, FormStateInterface $form_state) {
    $start = mm_ui_mmlist_key0($element['#value']);
    // Get the value of mm_list_popup_root by referring to its sibling $element.
    $root_parents = $element['#parents'];
    array_splice($root_parents, -1, 1, ['mm_list_popup_root']);
    $root = mm_ui_mmlist_key0($form_state->getValue($root_parents));
    if (!in_array($root, mm_content_get_parents_with_self($start))) {
      $form_state->setError($element, t('The default location in the tree must be the same as, or a child of, the topmost location.'));
    }
  }

}
