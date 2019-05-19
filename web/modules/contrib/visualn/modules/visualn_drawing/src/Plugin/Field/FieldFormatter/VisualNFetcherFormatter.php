<?php

namespace Drupal\visualn_drawing\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\Manager\DrawingFetcherManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Plugin implementation of the 'visualn_fetcher' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_fetcher",
 *   label = @Translation("VisualN fetcher"),
 *   field_types = {
 *     "visualn_fetcher"
 *   }
 * )
 */
class VisualNFetcherFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The visualn drawing fetcher manager service.
   *
   * @var \Drupal\visualn\Manager\DrawingFetcherManager
   */
  protected $visualNDrawingFetcherManager;


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
      $container->get('plugin.manager.visualn.drawing_fetcher')
    );
  }

  /**
   * Constructs an ImageFormatter object.
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
   * @param \Drupal\visualn\Manager\DrawingFetcherManager $visualn_drawing_fetcher_manager
   *   The visualn drawing fetcher manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DrawingFetcherManager $visualn_drawing_fetcher_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->visualNDrawingFetcherManager = $visualn_drawing_fetcher_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'override' => FALSE,
      'fetcher_id' => '',
      'fetcher_config' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // Add an option to override stored value for the fetcher field,
    // can be useful when the field is hidden on form mode
    // especially with Default fetcher field provided in-code with entity type,
    // which can not have default value set via UI.
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-formatter-override-form-ajax';
    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => t('Override field value'),
      '#default_value' => $this->getSetting('override'),
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form += [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    // add one more level to have 'override' value already mapped in the #process callback
    $form['override_container']['#process'][] = [$this, 'processFetcherSettingsSubform'];

    return $form;
  }

  /**
   * Process callback for override settings form
   */
  public function processFetcherSettingsSubform(array $element, FormStateInterface $form_state, $form) {
    $field_name = $this->fieldDefinition->getName();
    $ajax_wrapper_id = $field_name . '-formatter-override-form-ajax';

    $element_parents = $element['#parents'];
    $base_element_parents = array_slice($element_parents, 0, -1);

    $override = $form_state->getValue(array_merge($base_element_parents, ['override']), $this->getSetting('override'));
    $fetcher_id = $form_state->getValue(array_merge($element_parents, ['fetcher_id']), $this->getSetting('fetcher_id'));

    if (!$override) {
      return $element;
    }

    // Get drawing fetchers plugins list
    $fetchers_list = [];
    $definitions = $this->visualNDrawingFetcherManager->getDefinitions();
    foreach ($definitions as $definition) {
      $fetchers_list[$definition['id']] = $definition['label'];
    }

    $element['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Fetcher ID'),
      '#default_value' => $this->getSetting('fetcher_id'),
      '#options' => $fetchers_list,
      '#ajax' => [
        'callback' => [$this, 'ajaxFetcherCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_option' => t('- Select fetcher -'),
      '#required' => TRUE,
    ];

    // @note: using $fetcher_id instead of 'fetcher_container' would reset fetcher settings at fetcher change
    //   even if both fetchers have the same keys in config form
    $element['fetcher_container'] = [
      //'#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      //'#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[$this, 'processFetcherContainerSubform']],
    ];

    $element['fetcher_container']['fetcher_config'] = ['#process' => [[$this, 'processFetcherContainerSubform']]];

    return $element;
  }


  /**
   * Process callback for fetcher configuration form
   */
  public function processFetcherContainerSubform(array $element, FormStateInterface $form_state, $form) {

    $override_base_element_parents = array_slice($element['#parents'], 0, -2);
    $fetcher_id = $form_state->getValue(array_merge($override_base_element_parents, ['fetcher_id']));
    if (!$fetcher_id) {
      return $element;
    }

    if ($fetcher_id == $this->getSetting('fetcher_id')) {
      $fetcher_config = $this->getSetting('fetcher_config');
    }
    else {
      $fetcher_config = [];
    }

    // Instantiate fetcher plugin
    $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // get entity type and bundle from entity display mode EntityViewDisplay entity
    $entity_type = $form_state->getFormObject()->getEntity()->getTargetEntityTypeId();
    $bundle = $form_state->getFormObject()->getEntity()->getTargetBundle();


    // @todo: also check for more info VisualNFetcherWidget and VisualNBlock code
    // Set "entity_type" and "bundle" contexts
    $context_entity_type = new Context(new ContextDefinition('string', NULL, TRUE), $entity_type);
    $fetcher_plugin->setContext('entity_type', $context_entity_type);

    $context_bundle = new Context(new ContextDefinition('string', NULL, TRUE), $bundle);
    $fetcher_plugin->setContext('bundle', $context_bundle);
    // @todo: see the note regarding setting context in VisualNResourceProviderItem class


    // Attache fetcher plugin validate and submit callbacks
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $element = $fetcher_plugin->buildConfigurationForm($element, $subform_state);
    $element['#element_validate'] = [[get_called_class(), 'validateFetcherSubForm']];
    // @todo: should be attached in #element_submit when implemented in core
    $element['#element_validate'][] = [get_called_class(), 'submitFetcherSubForm'];

    return $element;
  }

  /**
   * Validate callback for fetcher configuration subform
   */
  public static function validateFetcherSubForm(&$form, FormStateInterface $form_state) {
    $element_parents = $form['#parents'];

    // call fetcher validation callback
    $full_form = $form_state->getCompleteForm();
    $subform = $form;
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    // get fetcher_id and load corrsponding fetcher plugin
    $base_element_parents = array_slice($element_parents, 0, -2);
    $fetcher_id  = $form_state->getValue(array_merge($base_element_parents, ['fetcher_id']));
    // @todo: no need in fetcher_config here since validateConfigurationForm() should fully rely on form_state values

    // Instantiate fetcher plugin
    $fetcher_plugin = \Drupal::service('plugin.manager.visualn.drawing_fetcher')
			->createInstance($fetcher_id, []);
			//->createInstance($fetcher_id, $fetcher_config);

    $fetcher_plugin->validateConfigurationForm($subform, $sub_form_state);
  }

  /**
   * Submit callback for fetcher configuration subform
   */
  public static function submitFetcherSubForm(&$form, FormStateInterface $form_state) {
    $element_parents = $form['#parents'];

    // call fetcher submit callback
    $full_form = $form_state->getCompleteForm();
    $subform = $form;
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    // get fetcher_id and load corrsponding fetcher plugin
    $base_element_parents = array_slice($element_parents, 0, -2);
    $fetcher_id  = $form_state->getValue(array_merge($base_element_parents, ['fetcher_id']));
    // @todo: no need in fetcher_config here since submitConfigurationForm() should fully rely on form_state values

    // Instantiate fetcher plugin
    $fetcher_plugin = \Drupal::service('plugin.manager.visualn.drawing_fetcher')
			->createInstance($fetcher_id, []);
			//->createInstance($fetcher_id, $fetcher_config);

    $fetcher_plugin->submitConfigurationForm($subform, $sub_form_state);


    $override_base_element_parents = array_slice($element_parents, 0, -3);

    // remove "override_container" (and "fetcher_container") from values keys structure (should be done in submit)
    $fetcher_config_values = $form_state->getValue($element_parents, []);
    $form_state->setValue(array_merge($override_base_element_parents, ['fetcher_id']), $fetcher_id);
    $form_state->setValue(array_merge($override_base_element_parents, ['fetcher_config']), $fetcher_config_values);

    $form_state->unsetValue(array_merge($override_base_element_parents, ['override_container']));

    // @todo: though drawer_container is still being saved in the config (GenericDrawingFetcherBase issue)
    //   see GenericDrawingFetcherBase::submitConfigurationForm()
  }

  /**
   * Return fetcher settings override subform via ajax
   *
   * @return array
   *   The override fetcher settings subform render array.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element;
  }

  /**
   * Return fetcher config subform via ajax
   *
   * @return array
   *   The override fetcher config subform render array.
   */
  public static function ajaxFetcherCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element;
    //return $element['fetcher_container'];
  }



  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Override field value: @override', ['@override' => $this->getSetting('override') ? t('Yes') : t('No')]);
    if ($this->getSetting('override') && $this->getSetting('fetcher_id')) {
      $definition = \Drupal::service('plugin.manager.visualn.drawing_fetcher')->getDefinition($this->getSetting('fetcher_id'));
      $summary[] = t('Drawing fetcher: @fetcher_label', ['@fetcher_label' => $definition['label']]);
      // @todo: also show fetcher config summary when implemented
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // get render array for the delta
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // check 'override' setting
    $override = $this->getSetting('override');
    if ($override) {
      $fetcher_id = $this->getSetting('fetcher_id');
      $fetcher_config = $this->getSetting('fetcher_config');
      if ($fetcher_id) {
        // @todo: see VisualNFetcherItem::buildDrawing()
	$fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);
        // Set reference to the entity since fetcher plugin generally may need all entity fields.

        // @todo: replace "any" context type with an appropriate one
        // Set "current_entity" context
        $context_current_entity = new Context(new ContextDefinition('any', NULL, TRUE), $item->getEntity());
        $fetcher_plugin->setContext('current_entity', $context_current_entity);
        // @todo: see the note regarding setting context in VisualNResourceProviderItem class


        $drawing_markup = $fetcher_plugin->fetchDrawing();

        return $drawing_markup;
      }
    }

    // fetch drawing markup
    return $item->buildDrawing();
  }

}
