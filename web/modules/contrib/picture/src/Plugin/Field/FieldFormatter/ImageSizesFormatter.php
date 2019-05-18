<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\field\formatter\ImageSizesFormatter.
 */

namespace Drupal\picture\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin for image with sizes attribute formatter.
 *
 * @FieldFormatter(
 *   id = "picture_sizes_formatter",
 *   label = @Translation("Image with sizes"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ImageSizesFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'sizes' => '',
      'image_styles' => array(),
      'fallback_image_style' => '',
      'image_link' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['sizes'] = array(
      '#title' => $this->t('Sizes'),
      '#type' => 'textfield',
      '#description' => $this->t(
        'The value of the sizes attribute. See !link for more information.',
        array(
        '!link' => l($this->t('the spec'), 'http://www.whatwg.org/specs/web-apps/current-work/multipage/embedded-content.html#introduction-3:viewport-based-selection-2')
        )
      ),
      '#default_value' => $this->getSetting('sizes'),
      '#required' => TRUE,
    );

    $image_styles = image_style_options(FALSE);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('- empty image -');

    $elements['image_styles'] = array(
      '#title' => t('Image styles'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('image_styles'),
      '#options' => $image_styles,
      '#required' => TRUE,
    );

    $elements['fallback_image_style'] = array(
      '#title' => t('Fallback image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_styles'),
      '#options' => $image_styles,
      '#required' => TRUE,
    );

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $elements['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = $this->t('Sizes: @sizes', array('@sizes' => $this->getSetting('sizes')));

    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);
    $image_styles[RESPONSIVE_IMAGE_EMPTY_IMAGE] = $this->t('Empty image');
    $selected_styles = array_filter($this->getSetting('image_styles'));
    $summary[] = t(
      'Image styles: @styles',
      array(
        '@styles' => implode(', ', array_intersect_key($image_styles, $selected_styles)),
      )
    );

    $summary[] = t('Fallback image style: @style', array('@style' => $image_styles[$this->getSetting('fallback_image_style')]));

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    if (isset($link_types[$this->getSetting('image_link')])) {
      $summary[] = $link_types[$this->getSetting('image_link')];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    // Check if the formatter involves a link.
    $image_link_setting = $this->getSetting('image_link');
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        // @todo Remove when theme_image_formatter() has support for route name.
        $uri['path'] = $entity->getSystemPath();
        $uri['options'] = $entity->urlInfo()->getOptions();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $fallback_image_style = '';

    // Check if the user defined a custom fallback image style.
    if ($this->getSetting('fallback_image_style')) {
      $fallback_image_style = $this->getSetting('fallback_image_style');
    }

    // Collect cache tags to be added for each item in the field.
    $image_styles_to_load = array_filter($this->getSetting('image_styles'));
    if ($fallback_image_style) {
      $image_styles_to_load[] = $fallback_image_style;
    }
    $image_styles = ImageStyle::loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $all_cache_tags[] = $image_style->getCacheTag();
    }

    $cache_tags = NestedArray::mergeDeepArray($all_cache_tags);

    foreach ($items as $delta => $item) {

      if ($item->entity) {
        if (isset($link_file)) {
          $image_uri = $item->entity->getFileUri();
          $uri = array(
            'path' => file_create_url($image_uri),
            'options' => array(),
          );
        }

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[$delta] = array(
          '#theme' => 'image_sizes_formatter',
          '#attached' => array(
            'library' => array(
              'core/picturefill',
            )
          ),
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_styles' => array_filter($this->getSetting('image_styles')),
          '#fallback_image_style' => $this->getSetting('fallback_image_style'),
          '#sizes' => $this->getSetting('sizes'),
          '#path' => isset($uri) ? $uri : '',
          '#cache' => array(
            'tags' => $cache_tags,
          ),
        );
      }
    }
    return $elements;
  }

}
