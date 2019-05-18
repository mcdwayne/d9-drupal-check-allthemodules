<?php

namespace Drupal\fullscreen_gallery\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the fullscreen_gallery.
 *
 * @FieldFormatter(
 *  id = "fullscreen_gallery",
 *  label = @Translation("Fullscreen Gallery"),
 *  field_types = {"image"}
 * )
 */
class FullscreenGalleryFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'use_default' => TRUE,
      'right_sidebar_width' => '',
      'right_sidebar_width_type' => 'px',
      'disable_titles' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $image_styles = image_style_options(FALSE);

    // Set the image style for displaying images.
    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#options' => $image_styles,
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#description' => t('Select the image style to use.'),
    ];

    // Checkbox for using default fullscreen gallery settings.
    $element['use_default'] = [
      '#type' => 'checkbox',
      '#title' => t('Use default settings'),
      '#default_value' => $this->getSetting('use_default'),
      '#description' => t('Default settings are inherited from <a href="@url">admin/config/media/fullscreen-gallery</a>', ['@url' => Url::fromRoute('fullscreen_gallery.settings')->toString()]),
      '#required' => FALSE,
    ];

    // Right sidebar width value.
    $field_definition = $this->fieldDefinition;
    $field_name = $field_definition->getName();
    $element['right_sidebar_width'] = [
      '#type' => 'textfield',
      '#title' => t('Width of the right side bar'),
      '#default_value' => $this->getSetting('right_sidebar_width'),
      '#size' => 4,
      '#maxlength' => 4,
      '#element_validate' => [
        [$this, 'validateSidebarWidth'],
      ],
      // Hide this setting when the use default checkbox is checked.
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][use_default]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Right sidebar width type (px or %).
    $element['right_sidebar_width_type'] = [
      '#type' => 'select',
      '#title' => '',
      '#default_value' => $this->getSetting('right_sidebar_width_type'),
      '#description' => t('Right sidebar appears if there is any content on <em>Full screen gallery right sidebar</em> region, and a valid sidebar width is given.'),
      '#options' => [
        'px' => t('pixels'),
        'pe' => t('percent'),
      ],
      // Hide this setting when the use default checkbox is checked.
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][use_default]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Checkbox for disabling title display.
    $element['disable_titles'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable image titles'),
      '#default_value' => $this->getSetting('disable_titles'),
      '#description' => t("Hide image titles in fullscreen gallery."),
      '#required' => FALSE,
      // Hide this setting when the use default checkbox is checked.
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][use_default]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Validates given sidebar width.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateSidebarWidth(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Validate given right sidebar width: it must be a numeric value.
    if ($element['#value'] !== '' && (!is_numeric($element['#value']) || intval($element['#value']) != $element['#value'] || $element['#value'] < 0)) {
      $form_state->setErrorByName('right_sidebar_width', t('field must be a valid integer.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $image_styles = image_style_options(FALSE);

    $style_name = (isset($image_styles[$this->getSetting('image_style')])) ? $image_styles[$this->getSetting('image_style')] : t('Original image');

    $summary[] = t('Image style: @style', ['@style' => $style_name]);
    if ($this->getSetting('use_default')) {
      // If default settings used, provide link for updating default settings.
      $summary[] = t('Settings: <a href="@url">default</a>', ['@url' => Url::fromRoute('fullscreen_gallery.settings')->toString()]);
    }
    else {
      // Add custom settings to summary text.
      if ($this->getSetting('right_sidebar_width')) {
        $summary[] = t('Right sidebar: @width@type', ['@width' => $this->getSetting('right_sidebar_width'), '@type' => ($this->getSetting('right_sidebar_width_type') == 'pe' ? '%' : 'px')]);
      }
      else {
        $summary[] = t('Right sidebar disabled.');
      }
      if ($this->getSetting('disable_titles')) {
        $summary[] = t('Image titles disabled.');
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);
    $request = \Drupal::request();
    $destination = $request->query->get('destination');
    $request_uri = $request->getPathInfo();

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $entity = $items->getEntity();
    $entity_id = $entity->id();
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();

    $field_definition = $this->fieldDefinition;
    $field_name = $field_definition->getName();

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      $cache_contexts = [];

      $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);
      $item_attributes['id'] = 'fs_gallery_item_' . $entity_type_id . '_' . $entity_id . '_' . $field_name . '_' . $delta;

      $parameters = [
        'query' => [
          'destination' => $destination ? $destination : $request_uri,
          'clicked_image_id' => $item_attributes['id'],
        ],
      ];

      $url = Url::fromRoute('fullscreen_gallery.page',
        [
          'entity_type' => $entity_type_id,
          'entity_id' => $entity_id,
          'field_name' => $field_name,
          'image_delta' => $delta,
        ],
        $parameters
      );

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
          'contexts' => $cache_contexts,
        ],
      ];
    }

    return $elements;
  }

}
