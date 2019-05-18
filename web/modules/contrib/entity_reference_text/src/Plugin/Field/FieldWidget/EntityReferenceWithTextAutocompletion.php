<?php

namespace Drupal\entity_reference_text\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\Textfield;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_text_autocompletion",
 *   label = @Translation("Autocomplete with text"),
 *   description = @Translation(""),
 *   field_types = {
 *     "entity_references_text"
 *   }
 * )
 */
class EntityReferenceWithTextAutocompletion extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LinkFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_value = isset($items[$delta]->value) ? $this->convertStoredToInputValue($items[$delta]->value) : '';
    $id = Html::getId('entity_reference_text_autocompletion');
    $element['value'] = $element + [
      // @todo Ideally we would use #type hidden, but that one doesn't support
      //    description and all kind of goodness.
      '#type' => 'textfield',
      '#default_value' => $default_value,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => [
        'class' => ['js-hide'],
        'id' => $id,
      ],
      '#pre_render' => [
        [Textfield::class, 'preRenderTextfield'],
        [Textfield::class, 'preRenderGroup'],
        [$this, 'valuePreRender']
      ],
    ];

    $element['editable'] = [
      '#type' => 'container',
      '#attributes' => [
        'contenteditable' => 'true',
        'class' => ['js-text-full', 'text-full', 'js-shown', 'single-line'],
        'data-atjs' => TRUE,
        // Enforce an array, so it doesn't end up being a JSON object.
        // @todo Write a JavascriptTestCase for this line.
        'data-atjs-url' => "/entity_reference_text_autocomplete/{$this->fieldDefinition->getTargetEntityTypeId()}/{$this->fieldDefinition->getTargetBundle()}/{$this->fieldDefinition->getName()}",
        'data-atjs-input-element-id' => $id,
      ],
      [
        '#markup' => $default_value,
      ],
    ];

    $element['#attached']['library'][] = 'entity_reference_text/entity_reference_text';

    return $element;
  }

  /**
   * Encodes names for JS by replacing splaces with underscores.
   *
   * @param string $name
   *
   * @return string
   *
   * @see \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion::decodeNameFromJs
   */
  protected function encodeNameForJs($name) {
    return str_replace(' ', '_', $name);
  }

  /**
   * Decodes names coming from JS by removing underscores.
   *
   * @param string $name
   *
   * @return string
   *
   * @see \Drupal\entity_reference_text\Plugin\Field\FieldWidget\EntityReferenceWithTextAutocompletion::encodeNameForJs
   */
  protected function decodeNameFromJs($name) {
    return str_replace('_', ' ', $name);
  }

  /**
   * Removes the required HTML flag so the browser doesn't try to focus.
   *
   * Chrome for example tries to use the "required" HTML attribute to ensure any
   * input is in the text value. We hide our textfield though, which let's
   * chrome complain about it.
   *
   * @param array $form
   *   The form element.
   *
   * @return array
   *   The prerendered form element.
   */
  public function valuePreRender(array $form) {
    if (isset($form['#attributes']['required'])) {
      unset($form['#attributes']['required']);
    }
    return $form;
  }

  /**
   * Converts the stored value to a value which can be understood by the JS.
   *
   * @param string $value
   *
   * @return string
   */
  protected function convertStoredToInputValue($value) {
    if (preg_match_all('/\((\d+)\)/i', $value, $matches, PREG_SET_ORDER)) {
      $replacements = [];
      foreach ($matches as $match) {
        $entity_id = $match[1];
        if ($entity = $this->entityTypeManager->getStorage($this->fieldDefinition->getSetting('target_type'))->load($entity_id)) {
          $replacements['(' . $entity_id . ')'] = '@' . $this->encodeNameForJs($entity->label());
        }
      }
      $value = str_replace(array_keys($replacements), array_values($replacements), $value);
    }
    return $value;
  }

  /**
   * Converts the JS value to a value which can be stored by Drupal.
   *
   * @param string $value
   *
   * @return string
   */
  protected function convertInputToStoredValue($value) {
    $selection_plugin = $this->getSelectionPlugin($this->fieldDefinition);
    $replacements = [];
    if (preg_match_all('/(@\w+)/i', $value, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        // Remove @ from the string itself.
        $match_string = substr($match[1], 1);
        // Use an underscore for names with a space.
        $match_string = $this->decodeNameFromJs($match_string);
        if (($entities_by_bundles = $selection_plugin->getReferenceableEntities($match_string)) && $entities = $this->flattenBundles($entities_by_bundles)) {
          $keys = array_keys($entities);
          $replacement_keys = array_map(function ($entity_id) {
            return '(' . $entity_id . ')';
          }, $keys);
          $replacement_values = array_values($entities);
          $replacement_values = array_map(function ($string) {
            return '@' . $this->encodeNameForJs($string);
          }, $replacement_values);
          $replacements += array_combine($replacement_values, $replacement_keys);
        }
      }
    }

    $value = str_replace('&nbsp;', ' ', $value);
    $value = str_replace(array_keys($replacements), array_values($replacements), $value);
    return $value;
  }

  /**
   * Removes the bundle level keys from an array of entities by bundle.
   *
   * @param mixed[][] $entities_by_bundles
   *   An array of entity IDs keyed by bundle.
   *
   * @return mixed[]
   *   An array of entity IDs.
   */
  protected function flattenBundles(array $entities_by_bundles) {
    $entities = [];
    array_walk($entities_by_bundles, function ($entities_one_bundle) use (&$entities) {
      $entities += $entities_one_bundle;
    });
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as &$value) {
      if (!empty($value['value'])) {
        $value['value'] = $this->convertInputToStoredValue($value['value']);
      }
    }

    return $values;
  }

  /**
   * Returns the entity type ID of the ER target type.
   *
   * @return string
   */
  protected function getTargetEntityTypeId() {
    return $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');
  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   */
  protected function getSelectionPlugin(FieldDefinitionInterface $field) {
    $options = array(
      'target_type' => $field->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field->getSetting('handler'),
      'handler_settings' => $field->getSetting('handler_settings'),
    );
    return \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
  }

}
