<?php

namespace Drupal\image_hotspots\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\image_hotspots\Entity\ImageHotspot;

/**
 * Plugin implementation of the 'image_image' formatter.
 *
 * @FieldFormatter(
 *   id = "image_with_hotspots",
 *   label = @Translation("Image with Hotspots"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageHotspotsFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $image_style = !empty($this->getSetting('image_style')) ? $this->getSetting('image_style') : 'none';
    $field_name = $items->getName();
    $files = $this->getEntitiesToView($items, $langcode);

    $info = [
      'field_name' => $field_name,
      'image_style' => $image_style,
    ];

    /** @var \Drupal\file\FileInterface $file */
    foreach ($files as $delta => $file) {
      $info['fid'] = $file->id();
      $hotspots = ImageHotspot::loadByTarget($info);

      $editable = FALSE;
      // Load library for edit hotspots if user in permission.
      if ($this->currentUser->hasPermission('edit image hotspots')) {
        $editable = TRUE;
        $elements[$delta]['#attached']['library'][] = 'image_hotspots/edit';
      }

      // Attach hotspots data to js settings.
      /** @var \Drupal\image_hotspots\Entity\ImageHotspot $hotspot */
      $hotspots_to_show = [];
      foreach ($hotspots as $hid => $hotspot) {
        $title = $hotspot->getTitle();
        $description = $hotspot->getDescription();
        $link = $hotspot->getLink();
        $value = [
          'title' => $title,
          'description' => !is_null($description) ? $description : '',
          'link' => !is_null($link) ? $link : '',
        ];
        foreach ($hotspot->getCoordinates() as $coordinate => $val) {
          $value[$coordinate] = $val;
        }
        $hotspots_to_show[$hid] = $value;
      }

      // Add cache tag 'hotspots:field_name:fid:image_style'.
      $elements[$delta]['#cache']['tags'][] = 'hotspots:' . $info['field_name'] . ':' . $info['fid'] . ':' . $info['image_style'];
      // Attache libraries.
      $elements[$delta]['#attached']['drupalSettings']['image_hotspots'][$field_name][$file->id()][$image_style]['hotspots'] = $hotspots_to_show;
      $elements[$delta]['#attached']['library'][] = 'image_hotspots/view';
      // Change element theme from 'image_formatter'.
      $elements[$delta]['#theme'] = 'image_formatter_with_hotspots';
      // Add additional info for render.
      $elements[$delta]['#info'] = $info;
      $elements[$delta]['#info']['editable'] = $editable;
    }

    return $elements;
  }

}
