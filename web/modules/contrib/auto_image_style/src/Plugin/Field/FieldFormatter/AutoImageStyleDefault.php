<?php /**
 * @file
 * Contains \Drupal\auto_image_style\Plugin\Field\FieldFormatter\AutoImageStyleDefault.
 */

namespace Drupal\auto_image_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * @FieldFormatter(
 *  id = "auto_image_style_default",
 *  label = @Translation("Image auto orientation"),
 *  description = @Translation("Display image fields as portrait or landscape style"),
 *  field_types = {"image"}
 * )use Drupal\Core\Entity\EntityStorageInterface;
 */
class AutoImageStyleDefault extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style_portrait' => '',
      'image_style_landscape' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $elements['image_style_portrait'] = array(
      '#type' => 'select',
      '#title' => $this->t('Portrait image style'),
      '#options' => $image_styles,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('image_style_portrait'),
      '#description' => $this->t('Select the image style for portrait images'),
    );
    $elements['image_style_landscape'] = array(
      '#type' => 'select',
      '#title' => $this->t('Landscape image style'),
      '#options' => $image_styles,
      '#empty_option' => $this->t('None (original image)'),
      '#default_value' => $this->getSetting('image_style_landscape'),
      '#description' => $this->t('Select the image style for landscape images'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_portrait_setting = $this->getSetting('image_style_portrait');
    if (isset($image_styles[$image_style_portrait_setting])) {
      $summary[] = $this->t('Portrait image style: @style', array('@style' => $image_styles[$image_style_portrait_setting]));
    }
    else {
      $summary[] = $this->t('Portrait image style: Original image');
    }

    $image_style_landscape_setting = $this->getSetting('image_style_landscape');
    if (isset($image_styles[$image_style_landscape_setting])) {
      $summary[] = $this->t('Landscape image style: @style', array('@style' => $image_styles[$image_style_landscape_setting]));
    }
    else {
      $summary[] = $this->t('Landscape image style: Original image');
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

    $image_style_landscape_setting = $this->getSetting('image_style_landscape');
    $image_style_portrait_setting = $this->getSetting('image_style_portrait');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
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

    foreach ($files as $delta => $file) {
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;

      $image_style = $image_style_portrait_setting;
      if ($item->height < $item->width) {
        $image_style = $image_style_landscape_setting;
      }

      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style,
        '#url' => $url,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }

    return $elements;
  }
}
