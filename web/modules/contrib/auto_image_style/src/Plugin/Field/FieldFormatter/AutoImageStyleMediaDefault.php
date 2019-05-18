<?php

namespace Drupal\auto_image_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin for responsive media image formatter.
 *
 * @FieldFormatter(
 *   id = "auto_image_style_media_default",
 *   label = @Translation("Image auto orientation"),
 *   description = @Translation("Display image fields as portrait or landscape style"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @see \Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter
 * @see \Drupal\media_entity\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 */
class AutoImageStyleMediaDefault extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style_portrait' => '',
      'image_style_landscape' => '',
      'image_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $elements['image_style_portrait'] = [
      '#type' => 'select',
      '#title' => $this->t('Portrait image style'),
      '#options' => $image_styles,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('image_style_portrait'),
      '#description' => $this->t('Select the image style for portrait images'),
    ];
    $elements['image_style_landscape'] = [
      '#type' => 'select',
      '#title' => $this->t('Landscape image style'),
      '#options' => $image_styles,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('image_style_landscape'),
      '#description' => $this->t('Select the image style for landscape images'),
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $elements['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_portrait_setting = $this->getSetting('image_style_portrait');
    if (isset($image_styles[$image_style_portrait_setting])) {
      $summary[] = $this->t('Portrait image style: @style', ['@style' => $image_styles[$image_style_portrait_setting]]);
    }
    else {
      $summary[] = $this->t('Portrait image style: Original image');
    }

    $image_style_landscape_setting = $this->getSetting('image_style_landscape');
    if (isset($image_styles[$image_style_landscape_setting])) {
      $summary[] = $this->t('Landscape image style: @style', ['@style' => $image_styles[$image_style_landscape_setting]]);
    }
    else {
      $summary[] = $this->t('Landscape image style: Original image');
    }

    $link_types = [
      'content' => $this->t('Linked to content'),
      'file' => $this->t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    if (isset($link_types[$this->getSetting('image_link')])) {
      $summary[] = $link_types[$this->getSetting('image_link')];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overriden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $media = parent::getEntitiesToView($items, $langcode);
    $entities = [];
    foreach ($media as $media_item) {
      $entity = $media_item->thumbnail->entity;
      $entity->_referringItem = $media_item->thumbnail;
      $entities[] = $entity;
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'media') {
      $link_file = TRUE;
    }

    $image_style_landscape_setting = $this->getSetting('image_style_landscape');
    $image_style_portrait_setting = $this->getSetting('image_style_portrait');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_landscape_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_landscape_setting);
      $cache_tags_landscape = $image_style->getCacheTags();
      $cache_tags = Cache::mergeTags($cache_tags, $cache_tags_landscape);
    }
    if (!empty($image_style_portrait_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_portrait_setting);
      $cache_tags_portrait = $image_style->getCacheTags();
      $cache_tags = Cache::mergeTags($cache_tags, $cache_tags_portrait);
    }

    /** @var \Drupal\media_entity\MediaInterface $media_item */
    foreach ($files as $delta => $file) {
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Link the <picture> element to the original file.
      if (isset($link_file)) {
        $url = file_url_transform_relative(file_create_url($file->getFileUri()));
      }

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;

      $image_style = $image_style_portrait_setting;
      if ($item->height < $item->width) {
        $image_style = $image_style_landscape_setting;
      }

      $elements[$delta] = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => [],
        '#image_style' => $image_style,
        '#url' => $url,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $styles[] = $this->getSetting('image_style_landscape');
    $styles[] = $this->getSetting('image_style_portrait');
    // Add the image styles as dependency.
    foreach ($styles as $style_id) {
      if ($style_id && $style = $this->imageStyleStorage->load($style_id)) {
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media';
  }

}
