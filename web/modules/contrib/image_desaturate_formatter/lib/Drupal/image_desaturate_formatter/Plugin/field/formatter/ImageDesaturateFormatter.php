<?php

/**
 * @file
 * Definition of Drupal\image_desaturate_formatter\Plugin\field\formatter\ImageDesaturateFormatter.
 */

namespace Drupal\image_desaturate_formatter\Plugin\field\formatter;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'image_desaturate' formatter.
 *
 * @Plugin(
 *   id = "image_desaturate",
 *   module = "image_desaturate_formatter",
 *   label = @Translation("Image desaturate formatter"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "image_style" = "",
 *     "image_link" = "",
 *     "default_style" = "desaturate",
 *   }
 * )
 */
class ImageDesaturateFormatter extends FormatterBase {

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

    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    $element['default_style'] = array(
      '#title' => t('Default image style'),
      '#type' => 'select',
      '#options' => array(
        'desaturate' => t('Desaturate'),
        'default' => t('Default'),
      ),
      '#default_value' => $settings['default_style'],
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

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    $default_style = array(
      'default' => t('Default image style'),
      'desaturate' => t('Desaturate image style'),
    );
    $default_style_setting = $this->getSetting('default_style');
    if (isset($default_style[$default_style_setting])) {
      $summary[] = $default_style[$default_style_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $uri = $entity->uri();
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');
    $default_style_setting = $this->getSetting('default_style');
    foreach ($items as $delta => $item) {
      if (isset($link_file)) {
        $uri = array(
          'path' => file_create_url($item['uri']),
          'options' => array(),
        );
      }
      $elements[$delta] = array(
        '#theme' => 'image_desaturate_formatter',
        '#item' => $item,
        '#image_style' => $image_style_setting,
        '#path' => isset($uri) ? $uri : '',
        '#default_style' => $default_style_setting,
      );
    }

    return $elements;
  }

}
