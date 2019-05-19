<?php

/**
 * @file
 * Contains Drupal\TouchTouch\Plugin\Field\FieldFormatter.
 */

namespace Drupal\TouchTouch\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'brochure_link' formatter.
 *
 * @FieldFormatter(
 *   id = "touchtouch",
 *   label = @Translation("TouchTouch gallery"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class TouchTouchFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail_image_style' => 'thumbnail',
      'large_image_style' => 'large',
      'grouping' => 'field',
        ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $image_style_options = image_style_options();
    $grouping_options = touchtouch_grouping_options();

    $thumbnail_style = $image_style_options[$settings['thumbnail_image_style']];
    if (empty($thumbnail_style)) {
      $thumbnail_style = t('Plain URL');
    }

    $summary[] = t('Thumbnail image style: @image_style', array('@image_style' => $thumbnail_style));
    $summary[] = t('Enlarged image style: @image_style', array('@image_style' => $image_style_options[$settings['large_image_style']]));

    if (!empty($settings['grouping'])) {
      $summary[] = t('Grouped by @grouping', array('@grouping' => $grouping_options[$settings['grouping']]));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $image_style_options = image_style_options();
    $fields['thumbnail_image_style'] = array(
      '#type' => 'select',
      '#options' => $image_style_options + array('plain_url' => t('Plain URL')),
      '#title' => t('Image style for clickable image'),
      '#description' => t('The image style for the image that functions as the trigger for the gallery'),
      '#default_value' => $settings['thumbnail_image_style'],
    );

    $fields['large_image_style'] = array(
      '#type' => 'select',
      '#options' => $image_style_options,
      '#title' => t('Image style for the enlarged images'),
      '#description' => t('The image style for the images shown in the gallery.'),
      '#default_value' => $settings['large_image_style'],
    );

    $fields['grouping'] = array(
      '#type' => 'select',
      '#options' => touchtouch_grouping_options(),
      '#title' => t('Group the gallery by'),
      '#description' => t('Select on what level the pictures should be grouped in one gallery.'),
      '#default_value' => $settings['grouping'],
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();

    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $big_image_style = $settings['large_image_style'];
    $attributes = array('class' => array('touchtouch'));

    /*
      switch ($settings['grouping']) {
      default:
      case 'none':
      break;
      case 'field_item':
      // @todo Group the pictures by field item
      break;
      case 'field':
      $attributes['data-gallery'] = $field['field_name'];
      break;
      case 'views_view':
      $attributes['data-gallery'] = str_replace('_', '-', $settings['grouping']) . '-' . $display['views_view']->name;
      break;
      case 'views_row_id':
      $attributes['data-gallery'] = str_replace('_', '-', $settings['grouping']) . '-' . $display[$settings['grouping']];
      break;
      case 'views_field':
      $attributes['data-gallery'] = str_replace('_', '-', $settings['grouping']) . '-row-' . $display['views_row_id'] . '-field-' . $display['views_field']->field_info['id'];
      break;
      }/* */

    foreach ($files as $delta => $file) {
      $file_uri = $file->getFileUri();
      $image_link = empty($big_image_style) ? file_create_url($file_uri) : ImageStyle::load($big_image_style)->buildUrl($file_uri);

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $attributes; //array_merge($attributes, $item->_attributes);
      unset($item->_attributes);

      $link = Url::fromUri($image_link);
      $link->setOptions(['attributes' => $item_attributes]);

      switch ($settings['thumbnail_image_style']) {
        case 'plain_url':
          $image_link = Link::fromTextAndUrl(file_create_url($file_uri), $link);
          $image_link = $image_link->toRenderable();

          $elements[$delta] = array(
            '#markup' => render($image_link),
          );
          break;

        default:
          $image = array(
            '#theme' => 'image_formatter',
            '#item' => $item,
            '#image_style' => $settings['thumbnail_image_style'],
          );

          $image_link = Link::fromTextAndUrl(render($image), $link);
          $image_link = $image_link->toRenderable();

          $elements[$delta] = array(
            '#markup' => render($image_link),
          );
          break;
      }
    }

    $elements['#attached'] = array(
      'library' => array(
        'touchtouch/touchTouch',
      ),
    );
    return $elements;
  }

}
