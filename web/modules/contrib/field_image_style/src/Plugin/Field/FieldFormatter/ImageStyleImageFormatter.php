<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter.
 */

namespace Drupal\field_image_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Plugin implementation of the 'image_style_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "image_style_image_formatter",
 *   label = @Translation("Field Image Style formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageStyleImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'field_image_style' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = array();

    // @todo: find a way to get all image_style fields of the current entity bundle
    $entityfieldmanager = \Drupal::service('entity_field.manager');
    $fields_image_style = $entityfieldmanager->getFieldMapByFieldType('image_style');
    $fields_image_style = array_intersect_key($fields_image_style[$form['#entity_type']], array_flip($form['#fields']));
    foreach($fields_image_style as $field_name => $info) {
      if(in_array($form['#bundle'], $info['bundles'])) {
        $options[$field_name] = $field_name;
      }
    }

    $element['field_image_style'] = array(
      '#title' => t('Field image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('field_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $options,
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $field_image_style_setting = $this->getSetting('field_image_style');
    if ($field_image_style_setting) {
      $summary[] = t('Image style from field: @field_name', array('@field_name' => $field_image_style_setting));
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

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    // Get image style from image style field
    $image_style_setting = NULL;
    if($field_image_style_name = $this->getSetting('field_image_style')) {
      $entity = $items->getEntity();
      if(isset($entity->{$field_image_style_name})) {
        $image_style_setting = $entity->{$field_image_style_name}->value;
      }
    }

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }

    return $elements;
  }

}
