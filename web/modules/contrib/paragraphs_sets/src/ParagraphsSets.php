<?php

namespace Drupal\paragraphs_sets;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBaseInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Utitlity class for paragraphs_sets.
 */
class ParagraphsSets {

  /**
   * Get a list of all available sets.
   *
   * @param array $allowed_paragraphs_types
   *   Optional list of allowed paragraphs types.
   *
   * @return array
   *   List of all Paragraphs Sets.
   */
  public static function getSets(array $allowed_paragraphs_types = []) {
    $query = \Drupal::entityQuery('paragraphs_set');
    $config_factory = \Drupal::configFactory();
    $results = $query->execute();
    $sets = [];
    foreach ($results as $id) {
      /** @var \Drupal\Core\Config\ImmutableConfig $config */
      if (($config = $config_factory->get("paragraphs_sets.set.{$id}"))) {
        $data = $config->getRawData();
        if (!empty($allowed_paragraphs_types)) {
          $types_filtered = array_intersect(array_column($data['paragraphs'], 'type'), $allowed_paragraphs_types);
          if (count($types_filtered) !== count($data['paragraphs'])) {
            continue;
          }
        }
        $sets[$id] = $data;
      }
    }

    return $sets;
  }

  /**
   * Get an array of id => label of available sets.
   *
   * @return array
   *   Sets labels, keyed by id.
   */
  public static function getSetsOptions(array $allowed_paragraphs_types = [], $cardinality = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
    $sets_data = static::getSets($allowed_paragraphs_types);
    $opts = [];
    foreach ($sets_data as $k => $set) {
      if (($cardinality !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && (count($set['paragraphs']) > $cardinality)) {
        // Do not add sets having more paragraphs than allowed.
        continue;
      }
      $opts[$k] = $set['label'];
    }
    return $opts;
  }

  /**
   * Returns the machine name for default paragraph set.
   *
   * @param \Drupal\Core\Field\WidgetBaseInterface $widget
   *   The widget to operate on.
   *
   * @return string
   *   Machine name for default paragraph set.
   */
  public static function getDefaultParagraphTypeMachineName(WidgetBaseInterface $widget) {
    $default_type = $widget->getSetting('default_paragraph_type');
    $allowed_types = static::getSets();
    if ($default_type && isset($allowed_types[$default_type])) {
      return $default_type;
    }
    // Check if the user explicitly selected not to have any default Paragraph
    // set. Otherwise, if there is only one set available, that one is the
    // default.
    if ($default_type === '_none') {
      return NULL;
    }
    if (count($allowed_types) === 1) {
      return key($allowed_types);
    }

    return NULL;
  }

  /**
   * Builds select element for set selection.
   *
   * @param array $elements
   *   Form elements to build the selection for.
   * @param array $context
   *   Required context for the set selection.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $default
   *   Current selected set.
   *
   * @return array
   *   The form element array.
   */
  public static function buildSelectSetSelection(array $elements, array $context, FormStateInterface $form_state, $default = NULL) {
    /** @var \Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget $widget */
    $widget = $context['widget'];
    if (!($widget instanceof ParagraphsWidget)) {
      return [];
    }
    $settings = $widget->getThirdPartySettings('paragraphs_sets');

    $items = $context['items'];
    $field_definition = $items->getFieldDefinition();
    $field_name = $field_definition->getName();
    $title = $field_definition->getLabel();
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $field_parents = $context['form']['#parents'];
    $field_id_prefix = implode('-', array_merge($field_parents, [$field_name]));
    $field_wrapper_id = Html::getId($field_id_prefix . '-add-more-wrapper');
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);

    // Get a list of all Paragraphs types allowed in this field.
    $field_allowed_paragraphs_types = $widget->getAllowedTypes($field_definition);
    $options = static::getSetsOptions(array_keys($field_allowed_paragraphs_types), $cardinality);
    // Further limit sets available from widget settings.
    if (isset($settings['paragraphs_sets']['sets_allowed']) && count(array_filter($settings['paragraphs_sets']['sets_allowed']))) {
      $options = array_intersect_key($options, array_filter($settings['paragraphs_sets']['sets_allowed']));
    }

    $options = [
      '_none' => t('- None -'),
    ] + $options;

    $selection_elements = [
      '#type' => 'container',
      '#theme_wrappers' => ['container'],
      '#attributes' => [
        'class' => ['set-selection-wrapper'],
      ],
    ];
    $selection_elements['set_selection_select'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default,
      '#title' => t('@title set', ['@title' => $widget->getSetting('title')]),
      '#label_display' => 'hidden',
    ];

    $selection_elements['set_selection_button'] = [
      '#type' => 'submit',
      '#name' => strtr($field_id_prefix, '-', '_') . '_set_selection',
      '#value' => t('Select set'),
      '#attributes' => ['class' => ['field-set-selection-submit']],
      '#limit_validation_errors' => [
        array_merge($field_parents, [$field_name, 'set_selection']),
      ],
      '#submit' => [['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetSubmit']],
      '#ajax' => [
        'callback' => ['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetAjax'],
        'wrapper' => $field_wrapper_id,
        'effect' => 'fade',
      ],
    ];
    $selection_elements['set_selection_button']['#prefix'] = '<div class="paragraphs-set-button paragraphs-set-button-set">';
    $selection_elements['set_selection_button']['#suffix'] = t('for %type', ['%type' => $title]) . '</div>';

    if ($field_state['items_count'] && ($field_state['items_count'] < $cardinality || $cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) && !$form_state->isProgrammed() && $elements['#allow_reference_changes']) {
      $selection_elements['append_selection_button'] = [
        '#type' => 'submit',
        '#name' => strtr($field_id_prefix, '-', '_') . '_append_selection',
        '#value' => t('Append set'),
        '#attributes' => ['class' => ['field-append-selection-submit']],
        '#limit_validation_errors' => [
          array_merge($field_parents, [$field_name, 'append_selection']),
        ],
        '#submit' => [['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetSubmit']],
        '#ajax' => [
          'callback' => ['\Drupal\paragraphs_sets\ParagraphsSets', 'setSetAjax'],
          'wrapper' => $field_wrapper_id,
          'effect' => 'fade',
        ],
      ];
      $selection_elements['append_selection_button']['#prefix'] = '<div class="paragraphs-set-button paragraphs-set-button-append">';
      $selection_elements['append_selection_button']['#suffix'] = t('to %type', ['%type' => $title]) . '</div>';
    }

    return $selection_elements;
  }

  /**
   * Retrieves processing information about the widget from $form_state.
   *
   * This method is static so that it can be used in static Form API callbacks.
   *
   * @param array $parents
   *   The array of #parents where the field lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array with the following key/value pairs:
   *   - items_count: The number of widgets to display for the field.
   *   - array_parents: The location of the field's widgets within the $form
   *     structure. This entry is populated at '#after_build' time.
   */
  public static function getWidgetState(array $parents, $field_name, FormStateInterface $form_state) {
    return NestedArray::getValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name));
  }

  /**
   * Stores processing information about the widget in $form_state.
   *
   * This method is static so that it can be used in static Form API #callbacks.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $field_state
   *   The array of data to store. See getWidgetState() for the structure and
   *   content of the array.
   */
  public static function setWidgetState(array $parents, $field_name, FormStateInterface $form_state, array $field_state) {
    NestedArray::setValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name), $field_state);
  }

  /**
   * Returns the location of processing information within $form_state.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The location of processing information within $form_state.
   */
  public static function getWidgetStateParents(array $parents, $field_name) {
    // Field processing data is placed at
    // $form_state->get(['field_storage', '#parents', ...$parents..., '#fields',
    // $field_name]), to avoid clashes between field names and $parents parts.
    return array_merge(['field_storage', '#parents'], $parents, ['#fields', $field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSetAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function setSetSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];
    $button_type = end($button['#array_parents']);

    // Increment the items count.
    $widget_state = static::getWidgetState($parents, $field_name, $form_state);
    $widget_state['button_type'] = $button_type;

    if (isset($button['#set_machine_name'])) {
      $widget_state['selected_set'] = $button['#set_machine_name'];
    }
    else {
      $widget_state['selected_set'] = $element['set_selection']['set_selection_select']['#value'];
    }

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    $form_state->setRebuild();
  }

  /**
   * Prepares the widget state to add a new paragraph at a specific position.
   *
   * In addition to the widget state change, also user input could be modified
   * to handle adding of a new paragraph at a specific position between existing
   * paragraphs.
   *
   * @param array $widget_state
   *   Widget state as reference, so that it can be updated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $field_path
   *   Path to paragraph field.
   * @param int|mixed $new_delta
   *   Delta position in list of paragraphs, where new paragraph will be added.
   */
  public static function prepareDeltaPosition(array &$widget_state, FormStateInterface $form_state, array $field_path, $new_delta) {
    // Increase number of items to create place for new paragraph.
    $widget_state['items_count']++;

    // Default behavior is adding to end of list and in case delta is not
    // provided or already at end, we can skip all other steps.
    if (!is_numeric($new_delta) || intval($new_delta) >= $widget_state['real_item_count']) {
      return;
    }

    $widget_state['real_item_count']++;

    // Limit delta between 0 and "number of items" in paragraphs widget.
    $new_delta = max(intval($new_delta), 0);

    // Change user input in order to create new delta position.
    $user_input = NestedArray::getValue($form_state->getUserInput(), $field_path);

    // Rearrange all original deltas to make one place for the new element.
    $new_original_deltas = [];
    foreach ($widget_state['original_deltas'] as $current_delta => $original_delta) {
      $new_current_delta = $current_delta >= $new_delta ? $current_delta + 1 : $current_delta;

      $new_original_deltas[$new_current_delta] = $original_delta;
      $user_input[$original_delta]['_weight'] = $new_current_delta;
    }

    // Add information into delta mapping for the new element.
    $original_deltas_size = count($widget_state['original_deltas']);
    $new_original_deltas[$new_delta] = $original_deltas_size;
    $user_input[$original_deltas_size]['_weight'] = $new_delta;

    $widget_state['original_deltas'] = $new_original_deltas;
    NestedArray::setValue($form_state->getUserInput(), $field_path, $user_input);
  }

  /**
   * Check if form state is in translation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Drupal\Core\Entity\EntityInterface $host
   *   The host entity.
   *
   * @return bool
   *   TRUE if the form state is in translation mode, FALSE otherwise.
   */
  public static function inTranslation(FormStateInterface $form_state, EntityInterface $host) {
    $is_in_translation = FALSE;
    if (!$host->isTranslatable()) {
      return FALSE;
    }
    if (!$host->getEntityType()->hasKey('default_langcode')) {
      return FALSE;
    }
    $default_langcode_key = $host->getEntityType()->getKey('default_langcode');
    if (!$host->hasField($default_langcode_key)) {
      return FALSE;
    }

    if (!empty($form_state->get('content_translation'))) {
      // Adding a language through the ContentTranslationController.
      $is_in_translation = TRUE;
    }
    if ($host->hasTranslation($form_state->get('langcode')) && $host->getTranslation($form_state->get('langcode'))->get($default_langcode_key)->value == 0) {
      // Editing a translation.
      $is_in_translation = TRUE;
    }

    return $is_in_translation;
  }

}
