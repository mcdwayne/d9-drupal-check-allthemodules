<?php

namespace Drupal\svg_icon\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;
use Drupal\file\Entity\File;
use Drupal\svg_icon\Svg;

/**
 * Plugin implementation of the 'svg_icon_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "svg_icon_formatter",
 *   label = @Translation("Svg Icon"),
 *   field_types = {
 *     "svg_icon"
 *   }
 * )
 */
class SvgIconFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'use_canvas' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['use_canvas'] = [
      '#title' => t('Canvas fix for IE scaling of Sprites'),
      '#description' => t('Requires a height and width attribute on your sprite symbols. If this is off you may need to size your icons with css.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('use_canvas'),
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('use_canvas') ? t('Includes canvas fix for IE') : t('No canvas IE fix');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $file = File::load($item->target_id);
      // File references can be broken by workbench_moderation.
      if (!$file || !file_exists($file->getFileUri())) {
        return [
          '#markup' => $this->t('<!-- Missing svg file. -->'),
        ];
      }

      $svg_string = file_get_contents($file->getFileUri());
      $svg = new Svg($svg_string);
      if ($svg->isSprite()) {
        foreach ($svg->getChildren() as $fragment) {
          if ($item->svg_id == $fragment->getId()) {
            $wrapper_attributes = new Attribute([
              'title' => $fragment->getTitle(),
            ]);

            $svg_attributes = new Attribute([
              'height' => $fragment->getHeight(),
              'width' => $fragment->getWidth(),
              'viewBox' => $fragment->getViewBox(),
            ]);

            $elements[$delta] = [
              '#theme' => 'svg_icon',
              '#wrapper_attributes' => $wrapper_attributes,
              '#svg_attributes' => $svg_attributes,
              '#icon_url' => file_url_transform_relative($file->url()) . '#' . $item->svg_id,
              '#use_canvas' => $this->getSetting('use_canvas'),
              '#attached' => ['library' => ['svg_icon/svg_icon.formatter']],
            ];
          }
        }
      }
      else {
        $elements[$delta] = [
          '#theme' => 'image',
          '#uri' => file_url_transform_relative($file->getFileUri()),
        ];
      }
    }
    return $elements;
  }

}
