<?php

namespace Drupal\autocomplete_field_match\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Plugin implementation of the 'autocomplete_field_match' widget.
 *
 * @FieldWidget(
 *   id = "autocomplete_field_match",
 *   label = @Translation("Autocomplete Field Match"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AutocompleteFieldMatchWidget extends EntityReferenceAutocompleteWidget implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

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
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Constructs a WidgetBase object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, LanguageManager $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'autocomplete_field_match' => [],
      'afm_operator_and_or' => 'or',
      'afm_operator_where' => '=',
      'afm_operator_langcode' => [],
      'match_operator' => 'STARTS_WITH',
      'size' => '60',
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  private function getEntityReferenceFields($field_name, $bundle, $target_type, $target_bundles = '') {
    $field_names = [];
    $all_fields_in_type = $this->entityFieldManager->getFieldMap()[$target_type];
    foreach ($all_fields_in_type as $key => $value) {
      $entity = $this->entityTypeManager->getStorage('field_storage_config')->load($target_type . '.' . $key);
      // Remove base fields.
      if (!$entity || $entity->isBaseField() == TRUE) {
        unset($all_fields_in_type[$key]);
        continue;
      }
      // Do we have some bundle restrictions?
      if ($target_bundles) {
        foreach ($target_bundles as $bundle_restriction) {
          if (!in_array($bundle_restriction, $value['bundles'])) {
            unset($all_fields_in_type[$key]);
          }
        }
      }
      // Is this another entity reference field?
      // Sorry. Dont think we can go down this possibly
      // neverending rabbithole. execution timeout likely.
      if ($entity->getPropertyDefinition('entity')) {
        unset($all_fields_in_type[$key]);
        /*
        $definitions = $entity->getPropertyDefinition('entity')->getTargetDefinition()->getPropertyDefinitions();
        foreach ($definitions as $definition) {
        //if ($definition->isBaseField() == FALSE) {
        dpm($definition->getName());
        dpm($definition->getTargetBundle());
        dpm($definition->getTargetEntityTypeId());
        dpm($this->getEntityReferenceFields(
        $definition->getName(),
        $definition->getTargetBundle(),
        $definition->getTargetEntityTypeId(),
        $target_bundles = '')
        );
        //}
        }
         */
      }
      if (isset($all_fields_in_type[$key])) {
        $field_names[$field_name][$entity->id()] = $entity->id();
      }
    }
    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  private function getFieldNames($entity_type, $bundle) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $names = [];
    foreach ($fields as $field_name => $field_definition) {
      // Remove standardized fields.
      if ($field_definition->getFieldStorageDefinition()->isBaseField() == TRUE) {
        continue;
      }
      $bundle = $field_definition->getTargetBundle();
      // entity_target_type may be an entity that contains more fields
      // (ie paragraph), hence getSettings()['target_type'] below.
      $entity_target_type = $field_definition->getTargetEntityTypeId();
      if (!empty($bundle) && !empty($entity_target_type)) {
        // We have an entity reference field
        // we need to get referenced entity's fields.
        if (isset($field_definition->getSettings()['target_type']) && $target_type = $field_definition->getSettings()['target_type']) {
          $target_bundles = '';
          if (isset($field_definition->getSettings()['handler_settings']['target_bundles'])) {
            $target_bundles = $field_definition->getSettings()['handler_settings']['target_bundles'];
          }
          $names += $this->getEntityReferenceFields($field_definition->id(), $bundle, $target_type, $target_bundles);
        }
        else {
          $names[$entity_target_type . '.' . $field_name] = $entity_target_type . '.' . $field_name;
        }
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  private function getInstalledLanguages() {
    $languages = [];
    foreach ($this->languageManager->getLanguages() as $language) {
      $languages[$language->getId()] = $language->getName();
    }

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $names = $this->getFieldNames($form['#entity_type'], $form['#bundle']);
    $languages = $this->getInstalledLanguages();

    $element['autocomplete_field_match'] = [
      '#type' => 'select',
      '#title' => t('Autocomplete Field Match'),
      '#default_value' => $this->getSetting('autocomplete_field_match'),
      '#multiple' => TRUE,
      '#options' => $names,
      '#description' => t('Select a field or fields to try to match the user input in the autocomplete field to field(s) other than title when the entity is not selected.<br/><strong>NOTE that you CANNOT select entity reference fields inside entity reference fields!</strong>'),
    ];
    $element['afm_operator_and_or'] = [
      '#type' => 'radios',
      '#title' => t('Autocomplete Field Match Operator'),
      '#default_value' => $this->getSetting('afm_operator_and_or'),
      '#options' => [
        'and' => t('AND'),
        'or' => t('OR'),
      ],
      '#description' => t('Select the operator used to match the fields selected in Autocomplete Field Match.'),
    ];
    $element['afm_operator_where'] = [
      '#type' => 'radios',
      '#title' => t('Autocomplete Field Match Where Operator'),
      '#default_value' => $this->getSetting('afm_operator_where'),
      '#options' => [
        '=' => t('EQUAL TO'),
        '>' => t('GREATER THAN'),
        '<' => t('LESS THAN'),
        '>=' => t('GREATER OR EQUAL'),
        '<=' => t('LESS OR EQUAL'),
        'STARTS_WITH' => t('STARTS WITH'),
        'CONTAINS' => t('CONTAINS'),
        'ENDS_WITH' => t('ENDS WITH'),
      ],
      '#description' => t('Select the condition used to match the fields selected in Autocomplete Field Match.'),
    ];
    $element['afm_operator_langcode'] = [
      '#type' => 'select',
      '#title' => t('Autocomplete Field Match Language'),
      '#default_value' => $this->getSetting('afm_operator_langcode'),
      '#multiple' => TRUE,
      '#options' => $languages,
      '#description' => t('Select the language(s) used to match the fields selected in Autocomplete Field Match. Leave empty to select all languages.'),
    ];
    $element['match_operator'] = [
      '#type' => 'radios',
      '#title' => t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
    ];
    $element['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $operators = $this->getMatchOperatorOptions();
    $summary[] = t('Autocomplete matching: @match_operator', ['@match_operator' => $operators[$this->getSetting('match_operator')]]);
    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = t('No placeholder');
    }
    $field_match = $this->getSetting('autocomplete_field_match');
    $field_matches = '';
    foreach ($field_match as $field_name) {
      $field_matches .= $field_name . ', ';
    }
    $field_matches = rtrim($field_matches, ', ');
    if (!empty($field_match)) {
      $summary[] = t('AFM field match: @autocomplete_field_match', ['@autocomplete_field_match' => $field_matches]);
    }
    else {
      $summary[] = t('AFM no fields set to match');
    }
    $summary[] = t('AFM operator: @operator', ['@operator' => $this->getSetting('afm_operator_and_or')]);
    $summary[] = t('AFM where operator: @where', ['@where' => $this->getSetting('afm_operator_where')]);
    $languages_to_search = $this->getSetting('afm_operator_langcode');
    $languages = '';
    foreach ($languages_to_search as $language) {
      $languages .= $language . ', ';
    }
    $languages = rtrim($languages, ', ');
    if (!empty($languages)) {
      $summary[] = t('AFM languages to search: @afm_operator_langcode', ['@afm_operator_langcode' => $languages]);
    }
    else {
      $summary[] = t('AFM search all languages');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + ['match_operator' => $this->getSetting('match_operator')];
    $selection_settings += ['autocomplete_field_match' => $this->getSetting('autocomplete_field_match')];
    $selection_settings += ['afm_operator_and_or' => $this->getSetting('afm_operator_and_or')];
    $selection_settings += ['afm_operator_where' => $this->getSetting('afm_operator_where')];
    $selection_settings += ['afm_operator_langcode' => $this->getSetting('afm_operator_langcode')];

    $element += [
      '#type' => 'autocomplete_field_match',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
    ];

    if ($this->getSelectionHandlerSetting('auto_create') && ($bundle = $this->getAutocreateBundle())) {
      $element['#autocreate'] = [
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
      ];
    }

    return ['target_id' => $element];
  }

}
