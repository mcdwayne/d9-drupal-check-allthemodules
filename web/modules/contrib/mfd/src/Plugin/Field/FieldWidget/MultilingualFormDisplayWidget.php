<?php

namespace Drupal\mfd\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'multilingual_form_display_widget' widget.
 *
 * @FieldWidget(
 *   id = "multilingual_form_display_widget",
 *   label = @Translation("Multilingual Form Display"),
 *   field_types = {
 *     "multilingual_form_display"
 *   },
 * )
 */
class MultilingualFormDisplayWidget extends WidgetBase  {


  /**
   * State indicating all collapsible fields are removed.
   */
  const COLLAPSIBLE_STATE_NONE = -1;

  /**
   * State indicating all collapsible fields are closed.
   */
  const COLLAPSIBLE_STATE_ALL_CLOSED = 0;

  /**
   * State indicating all collapsible fields are closed except the first one.
   */
  const COLLAPSIBLE_STATE_FIRST = 1;

  /**
   * State indicating all collapsible fields are open.
   */
  const COLLAPSIBLE_STATE_ALL_OPEN = 2;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'display_label' => TRUE,
        'display_description' => TRUE,
        'collapsible_state' => self::COLLAPSIBLE_STATE_FIRST,
        'mfd_languages' => array(),
      ] + parent::defaultSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $field_definition = $this->fieldDefinition;
    $values = $form_state->getValues();
    $fields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('multilingual_form_display');

    $elements['display_label'] = [
      '#title' => $this->t('Display the label in the form'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_label'),
    ];

    $elements['display_description'] = [
      '#title' => $this->t('Display the description in the form'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_description'),
    ];

    $collapsible_state_options = [
      self::COLLAPSIBLE_STATE_NONE => $this->t('Not collapsible -- all visible'),
      self::COLLAPSIBLE_STATE_ALL_CLOSED => $this->t('Collapsible and all closed'),
      self::COLLAPSIBLE_STATE_FIRST => $this->t('Collapsible with first language open'),
      self::COLLAPSIBLE_STATE_ALL_OPEN => $this->t('Collapsible and all open'),
    ];

    $elements['collapsible_state'] = [
      '#title' => $this->t('Choose whether the languages will be displayed in a collapsible field or not.'),
      '#type' => 'select',
      '#options' => $collapsible_state_options,
      '#default_value' => $this->getSetting('collapsible_state'),
    ];

    $available_langcodes = (\Drupal::languageManager()->getLanguages());
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    unset($available_langcodes[$default_langcode]);

    foreach ($available_langcodes as $key => $lang_obj) {
      $languages[$key] = [ 'lang' => $lang_obj->getName() ];
    }

    $elements['mfd_languages_markup'] = [
      '#type' => 'item',
      '#title' => $this->t('Choose languages to display'),
      '#markup' => $this->t('<p>You may select to have all the languages displayed in one field or pick and choose which ones to make visible. Each MFD field can display its own language and effectively swap out the current language for the one associated here.</p>'),
    ];

    $header = [
      'lang' => $this->t('Language'),
    ];
    $elements['mfd_languages'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $languages,
      '#empty' => $this->t('No languages found'),
      '#default_value' => $this->getSetting('mfd_languages'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $display_label = $this->getSetting('display_label');
    $display_description = $this->getSetting('display_description');
    $collapsible_state = $this->getSetting('collapsible_state');
    $languages = $this->getSetting('mfd_languages');

    if (!empty($display_label)) {
      $summary[] = $this->t('Label Displayed');
    }
    if (!empty($display_description)) {
      $summary[] = $this->t('Description Displayed');
    }
    if (!empty(array_filter($languages))) {
      $summary[] = $this->t('Languages Displayed: @languages', ['@languages' => implode(' | ', $this->getLanguageNames($languages))]);
    }

    switch ($collapsible_state) {
      case self::COLLAPSIBLE_STATE_NONE:
        $summary[] = $this->t('This field will be open and non-collapsible.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_CLOSED:
        $summary[] = $this->t('This field will collapsed by default.');
        break;

      case self::COLLAPSIBLE_STATE_FIRST:
        $summary[] = $this->t('This field will have the first language open and the others collapsed.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_OPEN:
        $summary[] = $this->t('This field will have all languages open and collapsible.');
        break;

    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */

//  The idea here is to collect all the translation field widgets
//  set them accordingly to their language values -- as in the hook_widget_form_alter()
//  then we have this single formElement be a TREE of several elements.


  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('edit multilingual form')) {
      return $element;
    }

    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    // Language Manager and objects
    $language_manager = \Drupal::languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();

    // Here we identify which interface we're in. Since we have to show different
    // form elements depending on the instance. Manage Fields will use FieldConfigInterface
    // and Node Edit will use NodeInterface.
    if ($entity instanceof FieldConfigInterface) {

    }

    if ($entity instanceof NodeInterface) {

      $entity_type_id = $entity->getEntityTypeId();
      $entity_type = \Drupal::service('entity.manager')->getDefinition($entity_type_id);
      $form_display = $form_state->getStorage('entity_form_display')['form_display'];

      $element += [
          '#type' => 'item',
          '#tree' => TRUE,
          '#weight' => 1,
          '#description_display' => 'before',
        ];

      if (empty($this->getSetting('display_label'))) {
        unset($element['#title']);
      }

      if (empty($this->getSetting('display_description'))) {
        unset($element['#description']);
      }

      $available_langcodes = ($language_manager->getLanguages());
      unset($available_langcodes[$current_language]);

      $selected_languages = $this->getSetting('mfd_languages');

//      if ($this->getSetting('default_swap_field')) {
      if (array_key_exists($current_language, array_flip($selected_languages))) {
        $default_language = $language_manager->getDefaultLanguage()->getId();
        unset($selected_languages[$current_language]);
        $selected_languages[$default_language] = $default_language;
      }

      $available_langcodes = array_intersect_key($available_langcodes, array_flip($selected_languages));
      reset($available_langcodes);

      $first_language = key($available_langcodes);

      foreach ($available_langcodes as $langcode => $language) {
        $form_state->set('language',$language );

        $langcode = $language->getId();
        $language_name = $language->getName();

        if ($entity->hasTranslation($langcode)) {
          $translated_entity = $entity->getTranslation($langcode);
        } else {
          $translated_entity = $entity->addTranslation($langcode);
          $translated_entity->set('title', 'untitled');
        }

        $element['value'][$langcode] = array(
          '#title' => $language_name,
        );
        // Create a container for the entity's fields.
        $collapsible_state = $this->getSetting('collapsible_state');
        if ($collapsible_state == self::COLLAPSIBLE_STATE_NONE) {
          $element['value'][$langcode] += [
            '#type' => 'item',
          ];
        } else {

          $element['value'][$langcode] += [
            '#type' => 'details',
            '#open' => ($langcode === $first_language && $collapsible_state == self::COLLAPSIBLE_STATE_FIRST) || ($collapsible_state == self::COLLAPSIBLE_STATE_ALL_OPEN) ? TRUE : FALSE,
          ];
        }
//            case COLLAPSIBLE_STATE_NONE:
//            case COLLAPSIBLE_STATE_ALL_CLOSED:
//            case COLLAPSIBLE_STATE_FIRST:
//            case COLLAPSIBLE_STATE_ALL_OPEN:


        foreach ($translated_entity->getFieldDefinitions() as $field_name => $definition) {
          $storage_definition = $definition->getFieldStorageDefinition();

          if (($definition->isComputed() || (!empty($storage_definition)  && $this->isFieldTranslatabilityConfigurable($entity_type, $storage_definition))) && $definition->isTranslatable()) {

            $translated_items = $translated_entity->get($field_name);
            $translated_items->filterEmptyItems();
            $translated_form = ['#parents' => []];
            $widget = $form_display->getRenderer($field_name);

            $component_form = $widget->form($translated_items, $translated_form, $form_state);
            // Now we have to do a bit of massaging to ensure namespace collision
            // in widgets doesn't happen. So, we'll modify a few values
            $field_name_with_ident = $this->getUniqueName($field_name, $langcode);
            $component_form['#field_name'] = $field_name_with_ident;
            $component_form['#multiform_display_use'] = TRUE;

            $component_form['widget']['#field_name'] = $field_name_with_ident;
            $parents_flipped = array_flip($component_form['widget']['#parents']);
            $component_form['widget']['#parents'][$parents_flipped[$field_name]] = $field_name_with_ident;

            // Create a container for the entity's fields.
            $element['value'][$langcode][$field_name] = $component_form;

          }
        }
      }
    }

    return $element;
  }

  /**
   * Helper Methods
   */

  /**
   * Checks whether translatability should be configurable for a field.
   *
   * N.B.: Instead of module_load_include('inc', 'content_translation', 'content_translation.admin')
   * which could be changed in the future or drop altogether, we've appropriated the helper
   * function. Since this is likely not to change and we are likely to use it more often, it
   * saves memory to have it here as opposed to loading an entire external file's worth
   * of functions which would have no purpose for us.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if field translatability can be configured, FALSE otherwise.
   *
   * @internal
   */
  protected function isFieldTranslatabilityConfigurable(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $definition) {
    // Allow to configure only fields supporting multilingual storage. We skip our
    // own fields as they are always translatable. Additionally we skip a set of
    // well-known fields implementing entity system business logic.
    return
      $definition->isTranslatable() &&
      $definition->getProvider() != 'content_translation' &&
      !in_array($definition->getName(), [
        $entity_type->getKey('langcode'),
        $entity_type->getKey('default_langcode'),
        'revision_translation_affected',
        $this->fieldDefinition->getName(),
      ]) &&
      !in_array($definition->getType(), [
        'multilingual_form_display',
      ])
      ;
  }

  /**
   * Creates a unique identifier
   *
   * @param string
   *   The field name.
   * @param string
   *   The language code.
   *
   * @return string
   *   A concatenated string between the field name and the language code.
   *
   * @internal
   */
  public function getUniqueName($field_name = 'stub', $langcode = '__') {
    return $field_name . '_' . $langcode;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    return array();
  }

  protected function getLanguageNames($languages) {
    // Language Manager and objects
    $language_manager = \Drupal::languageManager();
    $available_langcodes = ($language_manager->getLanguages());

    foreach ($languages as $key => $name) {
      if ($name != FALSE) {
        $languages_names[] = $available_langcodes[$key]->getName();
      }
    }

    return $languages_names;
  }

}

