<?php

/**
 * @file
 * Contains \Drupal\image_shadowbox\Plugin\field\formatter\ImageShadowboxFormatter.
 */

namespace Drupal\image_shadowbox\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'image_shadowbox' formatter.
 *
 * @FieldFormatter(
 *   id = "image_shadowbox",
 *   label = @Translation("Shadowbox"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageShadowboxFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'image_style' => '',
      'image_link' => '',
      'gallery' => '',
      'compact' => '',
      'title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );

    $gallery_options = array(
      'page' => 'gallery page',
      'field' => 'gallery field page',
      'nid' => 'gallery entity',
      'field_nid' => 'gallery field entity',
    );
    $element['gallery'] = array(
      '#title' => t('gallery'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('gallery'),
      '#empty_option' => t('None (individual)'),
      '#options' => $gallery_options,
    );

    $element['compact'] = array(
      '#title' => t('compact'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('compact'),
    );

    $title_options = array(
      'title' => 'image title',
      'alt' => 'image alt',
      'node' => 'node title',
    );
    $element['title'] = array(
      '#title' => t('caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('title'),
      '#empty_option' => t('None'),
      '#options' => $title_options,
    );

    return $element;
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
    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', array('@style' => $image_styles[$image_style_setting]));
    }
    else {
      $summary[] = t('Original image');
    }

    if (isset($image_styles[$this->getSetting('image_link')])) {
      $summary[] = t('Linked to: @style', array('@style' => $image_styles[$this->getSetting('image_link')]));
    }
    else {
      $summary[] = t('Linked to: Original image');
    }

    $gallery_options = array(
      'page' => 'gallery page',
      'field' => 'gallery field page',
      'nid' => 'gallery entity',
      'field_nid' => 'gallery field entity',
    );

    if (isset($gallery_options[$this->getSetting('gallery')])) {
      $summary[] = t('as @gallery', array('@gallery' => ($this->getSetting('compact') ? 'compact ' : '') . $gallery_options[$this->getSetting('gallery')]));
    }

    $title_options = array(
      'title' => 'image title',
      'alt' => 'image alt',
      'node' => 'node title',
    );

    if (isset($title_options[$this->getSetting('title')])) {
      $summary[] = t('with @title as caption', array('@title' => $title_options[$this->getSetting('title')]));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    $config = \Drupal::config('shadowbox.settings');
    $entity = $items->getEntity();

    $shadowbox_enabled_path = _shadowbox_activation() && $config->get('shadowbox_enabled');

    switch ($this->getSetting('gallery')) {
      case 'page':
        $gallery_id = 'gallery';
        break;

      case 'field':
        $gallery_id = $items->getName();
        break;

      case 'nid':
        $gallery_id = implode('-', array($entity->getEntityTypeId(), $entity->id()));
        break;

      case 'field_nid':
        $gallery_id = implode('-', array($entity->getEntityTypeId(), $entity->id(), $items->getName()));
        break;

      default:
        $gallery_id = '';
        break;
    }

    $rel = ($gallery_id != '') ? "shadowbox[$gallery_id]" : 'shadowbox';
    $class = ($gallery_id != '') ? "sb-image sb-gallery sb-gallery-$gallery_id" : 'sb-image sb-individual';
    $compact = $this->getSetting('compact');

    foreach ($items as $delta => $item) {
      if ($item->entity) {
        switch ($this->getSetting('title')) {
          case 'alt':
            $title = $item->alt;
            break;

          case 'title':
            $title = $item->title;
            break;

          case 'node':
            $title = $items->getEntity()->label();
            break;

          default:
            $title = '';
            break;
        }

        $linked_style = $this->getSetting('image_link');
        if ($linked_style) {
          $style = entity_load('image_style', $linked_style);
          $uri = $style->buildUrl($item->entity->getFileUri());
        }
        else {
          $uri = $item->entity->getFileUri();
        }

        $shadowbox_thumbnail = array(
          '#theme' => 'shadowbox_thumbnail',
          '#path' => $item->entity->getFileUri(),
          '#alt' => $item->alt,
          '#title' => $title,
          '#image_style' => $this->getSetting('image_style'),
        );

        $elements[$delta] = array(
          '#theme' => 'shadowbox_formatter',
          '#innerHTML' => ($delta == 0 || !$compact) ? $shadowbox_thumbnail : '',
          '#title' => $title,
          '#url' => file_create_url($uri),
          '#rel' => $rel,
          '#class' => $class,
        );

        if ($shadowbox_enabled_path) {
          $elements[$delta]['#attached']['library'][] = 'shadowbox/shadowbox';
        }
      }
    }

    return $elements;
  }
}