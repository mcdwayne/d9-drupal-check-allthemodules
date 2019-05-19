<?php

namespace Drupal\taxonomy_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'taxonomy_image_term_reference_image' formatter.
 *
 * @FieldFormatter(
 *  id = "taxonomy_image_term_reference_image",
 *  label = @Translation("Image"),
 * field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TaxonomyImageTermReferenceImage extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'image_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];
    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
    ];
    $element['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    ];

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

    // Image link summary.
    $link_types = [
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    $image_style_setting = $this->getSetting('image_style');
    $image_link_setting = $this->getSetting('image_link');
    $url = NULL;

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Check if the formatter involves a link.
      if ($image_link_setting == 'content') {
        if (!$entity->isNew()) {
          $url = $entity->urlInfo();
        }
      }
      elseif ($image_link_setting == 'file') {
        $link_file = TRUE;
      }

      if (isset($link_file)) {
        $target_id = Term::load($entity->id())->get('taxonomy_image')->getValue()[0]['target_id'];
        if ($target_id) {
          $file = File::load($target_id);
          $image_uri = $file->getFileUri();
          $url = Url::fromUri(file_create_url($image_uri));
        }
      }

      $item = $entity->get('taxonomy_image')->offsetGet($delta);
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      // @TODO Add #cache invalidation.
      $elements[$delta] = array(
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
      );
    }

    return $elements;
  }

}
