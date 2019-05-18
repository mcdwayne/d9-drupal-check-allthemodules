<?php

namespace Drupal\auto_image_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;

/**
 * Plugin for responsive media image formatter.
 *
 * @FieldFormatter(
 *   id = "auto_image_style_media_responsive",
 *   label = @Translation("Responsive image auto orientation"),
 *   description = @Translation("Display responsive image fields as portrait or landscape style"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @see \Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter
 * @see \Drupal\media_entity\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 */
class AutoImageStyleMediaResponsive extends ResponsiveImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_image_style_landscape' => '',
      'responsive_image_style_portrait' => '',
      'image_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_options = [];
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style_landscape'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive landscape image style'),
      '#options' => $responsive_image_options,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('responsive_image_style_landscape'),
      '#description' => $this->t('Select the responsive image style for landscape images'),
    ];
    $elements['responsive_image_style_portrait'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive portrait image style'),
      '#options' => $responsive_image_options,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('responsive_image_style_portrait'),
      '#description' => $this->t('Select the responsive image style for portrait images'),
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

    $responsive_landscape_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_landscape'));
    if ($responsive_landscape_image_style) {
      $summary[] = $this->t('Responsive landscape image style: @responsive_image_style', ['@responsive_image_style' => $responsive_landscape_image_style->label()]);
    }
    else {
      $summary[] = $this->t('Select a responsive landscape image style.');
    }

    $responsive_portrait_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_portrait'));
    if ($responsive_portrait_image_style) {
      $summary[] = $this->t('Responsive portrait image style: @responsive_image_style', ['@responsive_image_style' => $responsive_portrait_image_style->label()]);
    }
    else {
      $summary[] = $this->t('Select a responsive portrait image style.');
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
    // Check if the formatter involves a link.
    if ($this->getSetting('image_link') == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($this->getSetting('image_link') == 'file') {
      $link_file = TRUE;
    }

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    $image_styles_to_load = [];

    $responsive_image_style_landscape = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_landscape'));
    if ($responsive_image_style_landscape) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style_landscape->getCacheTags());
      $image_styles_to_load = $responsive_image_style_landscape->getImageStyleIds();
    }
    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    $responsive_image_style_portrait = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_portrait'));
    if ($responsive_image_style_portrait) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style_portrait->getCacheTags());
      $image_styles_to_load = $responsive_image_style_portrait->getImageStyleIds();
    }
    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    foreach ($files as $delta => $file) {
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Link the <picture> element to the original file.
      if (isset($link_file)) {
        $url = file_url_transform_relative(file_create_url($file->getFileUri()));
      }
      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;

      $responsive_image_style = $responsive_image_style_portrait;
      if ($item->height < $item->width) {
        $responsive_image_style = $responsive_image_style_landscape;
      }

      $elements[$delta] = [
        '#theme' => 'responsive_image_formatter',
        '#item' => $item,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#url' => $url,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $styles[] = $this->getSetting('responsive_image_style_landscape');
    $styles[] = $this->getSetting('responsive_image_style_portrait');
    // Add the responsive image styles as dependency.
    foreach ($styles as $style_id) {
      if ($style_id && $style = $this->responsiveImageStyleStorage->load($style_id)) {
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
