<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormComponent\FormComponentBase;
use Drupal\flexiform\FormComponent\ContainerFactoryFormComponentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component class for field widgets.
 */
class FieldWidgetComponent extends FormComponentBase implements ContainerFactoryFormComponentInterface {

  /**
   * Renderer item.
   *
   * @var \Drupal\Core\Field\PluginSettingsInterface
   */
  protected $renderer;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $pluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $name, array $options, FlexiformEntityFormDisplay $form_display) {
    return new static(
      $name,
      $options,
      $form_display,
      $container->get('plugin.manager.field.widget'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($name, $options, FlexiformEntityFormDisplay $form_display, WidgetPluginManager $plugin_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($name, $options, $form_display);

    $this->moduleHandler = $module_handler;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Set the field definition.
   */
  public function setFieldDefinition(FieldDefinitionInterface $field_definition) {
    $this->fieldDefinition = $field_definition;
  }

  /**
   * Get the renderer.
   *
   * @return \Drupal\Core\Field\PluginSettingsInterface
   *   The plugin settings.
   */
  protected function getRenderer() {
    if (!empty($this->renderer)) {
      return $this->renderer;
    }

    // Instantiate a widget object for the display properties.
    if (isset($this->options['type']) && ($definition = $this->getFieldDefinition())) {
      $widget = $this->pluginManager->getInstance([
        'field_definition' => $definition,
        'form_mode' => $this->getFormDisplay()->getOriginalMode(),
        'prepare' => FALSE,
        'configuration' => $this->options,
      ]);
    }
    else {
      $widget = NULL;
    }

    $this->renderer = $widget;
    return $widget;
  }

  /**
   * Get the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The field definition.
   */
  public function getFieldDefinition() {
    return $this->fieldDefinition;
  }

  /**
   * Render the component in the form.
   */
  public function render(array &$form, FormStateInterface $form_state, RendererInterface $renderer) {
    if ($widget = $this->getRenderer()) {
      if (strpos($this->name, ':')) {
        list($namespace, $field_name) = explode(':', $this->name, 2);

        // This is a form entity element so we need to tweak parents so that
        // form state values are grouped by entity namespace.
        $form['#parents'][] = $namespace;

        // Get the items from the entity manager.
        if ($form_entity = $this->getFormEntityManager()->getEntity($namespace)) {
          $items = $form_entity->get($field_name);
        }
        else {
          // Skip this component if we can't get hold of an entity.
          return;
        }
      }
      else {
        $items = $this->getFormEntityManager()->getEntity('')->get($this->name);
      }
      $items->filterEmptyItems();

      $form[$this->name] = $widget->form($items, $form, $form_state);
      $form[$this->name]['#access'] = $items->access('edit');

      // Assign the correct weight. This duplicates the reordering done in
      // processForm(), but is needed for other forms calling this method
      // directly.
      $form[$this->name]['#weight'] = $this->options['weight'];

      // Associate the cache tags for the field definition & field storage
      // definition.
      $field_definition = $this->getFieldDefinition();
      $renderer->addCacheableDependency($form[$this->name], $field_definition);
      $renderer->addCacheableDependency($form[$this->name], $field_definition->getFieldStorageDefinition());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array $form, FormStateInterface $form_state) {
    $original_parents = $form['#parents'];
    array_pop($original_parents);
    $form = [
      '#parents' => $original_parents,
      $this->name => $form,
    ];

    if (strpos($this->name, ':')) {
      list($namespace, $field_name) = explode(':', $this->name, 2);
    }
    else {
      $namespace = '';
      $field_name = $this->name;
    }

    // Get the entity object.
    if ($context = $this->getFormEntityManager()->getContext($namespace)) {
      $entity_object = $context->getContextValue();
      $items = $entity_object->{$field_name};
      if ($widget = $this->getRenderer()) {
        $widget->extractFormValues($items, $form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    if (count($this->getFormEntityManager()->getFormEntities()) > 1) {
      $namespace = $this->getEntityNamespace();
      return $this->getFieldDefinition()->getLabel() . ' [' . $this->getFormEntityManager()->getContext($namespace)->getContextDefinition()->getLabel() . ']';
    }
    return $this->getFieldDefinition()->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $plugin = $this->getRenderer();

    if ($plugin) {
      // Generate the settings form and allow other modules to alter it.
      $settings_form = $plugin->settingsForm($form, $form_state);
      $third_party_settings_form = $this->thirdPartySettingsForm($form, $form_state);

      if ($settings_form || $third_party_settings_form) {
        return [
          'settings' => $settings_form,
          'third_party_settings' => $third_party_settings_form,
        ];
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($plugin = $this->getRenderer()) {
      $summary = $plugin->settingsSummary();
    }

    $context = [
      'widget' => $plugin,
      'field_definition' => $this->getFieldDefinition(),
      'form_mode' => $this->getFormDisplay()->getMode(),
    ];
    $this->moduleHandler->alter('field_widget_settings_summary', $summary, $context);

    if ($plugin && $field_def = $plugin->getThirdPartySetting('flexiform', 'field_definition')) {
      if (!empty($field_def['label'])) {
        $summary[] = t('Label override: @label', ['@label' => $field_def['label']]);
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    $default_settings = $this->pluginManager->getDefaultSettings($this->options['type']);
    $options['settings'] = isset($values['settings']) ? array_intersect_key($values['settings'], $default_settings) : [];
    $options['third_party_settings'] = isset($values['third_party_settings']) ? $values['third_party_settings'] : [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function thirdPartySettingsForm(array $form, FormStateInterface $form_state) {
    $plugin = $this->getRenderer();
    $settings_form = [];
    foreach ($this->moduleHandler->getImplementations('field_widget_third_party_settings_form') as $module) {
      $settings_form[$module] = $this->moduleHandler->invoke($module, 'field_widget_third_party_settings_form', [
        $this->getRenderer(),
        $this->getFieldDefinition(),
        $this->getFormDisplay()->getMode(),
        $form,
        $form_state,
      ]);
    }

    $field_def = $plugin->getThirdPartySetting('flexiform', 'field_definition');
    $settings_form['flexiform']['field_definition']['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label Override'),
      '#default_value' => !empty($field_def['label']) ? $field_def['label'] : '',
    ];
    return $settings_form;
  }

}
