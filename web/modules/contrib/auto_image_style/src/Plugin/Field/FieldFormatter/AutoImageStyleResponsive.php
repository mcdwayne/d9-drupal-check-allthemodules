<?php /**
 * @file
 * Contains \Drupal\auto_image_style\Plugin\Field\FieldFormatter\AutoImageStyleDefault.
 */

namespace Drupal\auto_image_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;

/**
 * @FieldFormatter(
 *  id = "auto_image_style_responsive",
 *  label = @Translation("Responsive image auto orientation"),
 *  description = @Translation("Display responsive image fields as portrait or landscape style"),
 *  field_types = {"image"}
 * )use Drupal\Core\Entity\EntityStorageInterface;
 */
class AutoImageStyleResponsive extends ResponsiveImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'responsive_image_style_landscape' => '',
      'responsive_image_style_portrait' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_options = array();
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style_landscape'] = array(
      '#type' => 'select',
      '#title' => $this->t('Responsive landscape image style'),
      '#options' => $responsive_image_options,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('responsive_image_style_landscape'),
      '#description' => $this->t('Select the responsive image style for landscape images'),
    );
    $elements['responsive_image_style_portrait'] = array(
      '#type' => 'select',
      '#title' => $this->t('Responsive portrait image style'),
      '#options' => $responsive_image_options,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('responsive_image_style_portrait'),
      '#description' => $this->t('Select the responsive image style for portrait images'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $responsive_landscape_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_landscape'));
    if ($responsive_landscape_image_style) {
      $summary[] = $this->t('Responsive landscape image style: @responsive_image_style', array('@responsive_image_style' => $responsive_landscape_image_style->label()));
    }
    else {
      $summary[] = $this->t('Select a responsive landscape image style.');
    }

    $responsive_portrait_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style_portrait'));
    if ($responsive_portrait_image_style) {
      $summary[] = $this->t('Responsive portrait image style: @responsive_image_style', array('@responsive_image_style' => $responsive_portrait_image_style->label()));
    }
    else {
      $summary[] = $this->t('Select a responsive portrait image style.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Currently no link handling.
    $url = NULL;

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    $image_styles_to_load = array();

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

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;

      $responsive_image_style = $responsive_image_style_portrait;
      if ($item->height < $item->width) {
        $responsive_image_style = $responsive_image_style_landscape;
      }

      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'responsive_image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
        '#url' => $url,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }

    return $elements;
  }
}
