<?php

namespace Drupal\whitelabel\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\whitelabel\Entity\WhiteLabel;
use Drupal\whitelabel\WhiteLabelInterface;
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;

/**
 * Plugin implementation of the 'white label entity_reference' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_whitelabel",
 *   label = @Translation("White label"),
 *   description = @Translation("A white label inline form widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class InlineWhiteLabelWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'color_scanner' => TRUE,
      'form_display_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['color_scanner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable color scanner'),
      '#description' => $this->t('This shows the most used colors in the uploaded logo.'),
      '#default_value' => $this->getSetting('color_scanner'),
    ];

    $elements['form_display_mode'] = [
      '#type' => 'select',
      '#options' => \Drupal::service('entity_display.repository')->getFormModeOptions($this->getFieldSetting('target_type')),
      '#title' => $this->t('Form display mode'),
      '#description' => $this->t('The form display mode to use when rendering the white label form.'),
      '#default_value' => $this->getSetting('form_display_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Color scanner: @color_scanner', ['@color_scanner' => $this->getSetting('color_scanner') ? $this->t('Enabled') : $this->t('Disabled')]);
    $summary[] = $this->t('Form display mode: @form_display_mode', ['@form_display_mode' => $this->getSetting('form_display_mode')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (!$whitelabel_entity = $items[$delta]->entity) {
      $whitelabel_entity = WhiteLabel::create();
    }

    $field_name = $this->fieldDefinition->getName();
    $parents = $element['#field_parents'];

    $element_parents = $parents;
    $element_parents[] = $field_name;
    $element_parents[] = $delta;

    $element += [
      '#type' => 'details',
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['whitelabel-form'],
      ],
      '#parents' => $element_parents,
    ];

    $display = EntityFormDisplay::collectRenderDisplay($whitelabel_entity, $this->getSetting('form_display_mode'));
    $display->buildForm($whitelabel_entity, $element, $form_state);

    $element['theme']['widget']['#empty_option'] = $this->t('Use the default theme');
    unset($element['theme']['widget']['#options']['_none']);

    // Color specific settings.
    if (\Drupal::config('whitelabel.settings')->get('site_colors')) {
      // Add an ajax callback to the logo and theme widgets.
      $element['logo']['widget']['#ajax'] = $element['theme']['widget']['#ajax'] = [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'color_scheme_form',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Updating preview...'),
        ],
      ];

      // Prepare the container element for the color form.
      $element_parents[] = 'color_ajax_wrapper';

      $element['color_ajax_wrapper'] = [
        '#type' => 'container',
        '#parents' => [],
        '#attributes' => ['id' => 'color_scheme_form'],
        '#weight' => 15,
      ];

      // Get the default theme.
      $default_theme = \Drupal::config('system.theme')->get('default');

      // Show color form for form-theme, if not defined; use global WL theme,
      // use the global theme otherwise.
      $theme = $form_state->getValue([$field_name, 0, 'theme', 0, 'value']) ?: $whitelabel_entity->getTheme() ?: $default_theme;

      // Attach the color form.
      if (\Drupal::service('module_handler')->moduleExists('color') && color_get_info($theme) && function_exists('gd_info')) {
        $element['color_ajax_wrapper']['color'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Color scheme'),
          '#theme' => 'color_scheme_form',
          '#parents' => [],
          '#access' => \Drupal::config('whitelabel.settings')->get('site_colors'),
        ];

        $element['color_ajax_wrapper']['color'] += whitelabel_color_scheme_form($form, $form_state, $theme, $whitelabel_entity);

        // Attach whitelabel_color.js (customized color.js).
        $element['color_ajax_wrapper']['color']['scheme']['#attached']['library'][] = 'whitelabel/whitelabel.form';

        // Add validators (from color module).
        $element['#validate'][] = 'color_scheme_form_validate';

        // Add the color scanner if it was enabled and a logo was provided.
        if ($this->getSetting('color_scanner')
          && !empty($form_state->getValue([$field_name, 0, 'logo', 0, 'fids', 0]))) {
          if ($file = File::load($form_state->getValue([$field_name, 0, 'logo', 0, 'fids', 0]))) {
            $element['color_ajax_wrapper']['color_placeholder']['color_extractor'] = [
              '#markup' => '',
            ];

            $palette = Palette::fromFilename($file->getFileUri());
            $extractor = new ColorExtractor($palette);
            $colors = $extractor->extract(10);

            foreach ($colors as $color) {
              $element['color_ajax_wrapper']['color_placeholder']['color_extractor']['#markup'] .= Color::fromIntToHex($color) . ' ; ';
            }
          }
        }
      }
    }

    $widget_state['whitelabel'][$delta]['entity'] = $whitelabel_entity;
    $widget_state['whitelabel'][$delta]['display'] = $display;

    static::setWidgetState($parents, $field_name, $form_state, $widget_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $widget_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $element = NestedArray::getValue($form_state->getCompleteForm(), $widget_state['array_parents']);

    foreach ($values as $delta => &$item) {
      if (isset($widget_state['whitelabel'][$item['_original_delta']]['entity'])) {
        /** @var \Drupal\whitelabel\WhiteLabelInterface $whitelabel_entity */
        $whitelabel_entity = $widget_state['whitelabel'][$item['_original_delta']]['entity'];

        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
        $form_display = $widget_state['whitelabel'][$item['_original_delta']]['display'];
        $form_display->extractFormValues($whitelabel_entity, $element[$item['_original_delta']], $form_state);

        // A content entity form saves without any rebuild. It needs to set the
        // language to update it in case of language change.
        $langcode_key = $whitelabel_entity->getEntityType()->getKey('langcode');
        if ($whitelabel_entity->get($langcode_key)->value != $form_state->get('langcode')) {
          // If a translation in the given language already exists, switch to
          // that. If there is none yet, update the language.
          if ($whitelabel_entity->hasTranslation($form_state->get('langcode'))) {
            $whitelabel_entity = $whitelabel_entity->getTranslation($form_state->get('langcode'));
          }
          else {
            $whitelabel_entity->set($langcode_key, $form_state->get('langcode'));
          }
        }

        // Update the palette only if the theme supports it.
        if ($palette = $form_state->getValue('palette')) {
          // Insert palette.
          $whitelabel_entity->setPalette($palette);
        }

        // Get the owner of the parent entity so it can be set for white labels.
        if ($parent_entity_owner = $form_state->getValue('uid')[0]['target_id']) {
          $whitelabel_entity->setOwnerId($parent_entity_owner);
        }

        $whitelabel_entity->setNeedsSave(TRUE);
        $item['entity'] = $whitelabel_entity;
        $item['target_id'] = $whitelabel_entity->id();
        $item['target_revision_id'] = $whitelabel_entity->getRevisionId();
      }
    }
    return $values;
  }

  /**
   * Callback to update parts of the form after an AJAX request.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The changed portion of the form.
   */
  public function ajaxCallback(array &$form, FormStateInterface &$form_state) {
    $triggeringElement = $form_state->getTriggeringElement();

    // Todo: This is still a bit fragile.
    return $form[$triggeringElement['#field_parents'][0]]['widget'][$triggeringElement['#delta']]['color_ajax_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $parents = $form['#parents'];

    // Identify the manage field settings default value form.
    if (in_array('default_value_input', $parents, TRUE)) {
      // Since the entity is not reusable neither cloneable, having a default
      // value is not supported.
      return ['#markup' => $this->t('Default values are not supported for: %label.', ['%label' => $items->getFieldDefinition()->getLabel()])];
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow this widget for references to white label entities.
    $target_type = $field_definition->getSetting('target_type');
    $entity_type = \Drupal::entityTypeManager()->getDefinition($target_type);

    if ($entity_type) {
      return $entity_type->entityClassImplements(WhiteLabelInterface::class);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    // Filter possible empty items.
    $items->filterEmptyItems();
    return parent::extractFormValues($items, $form, $form_state);
  }

}
