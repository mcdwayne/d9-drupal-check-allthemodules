<?php

namespace Drupal\icon_field\Plugin\Field\FieldFormatter;

/**
 * @file
 * Contains \Drupal\icon_field\Plugin\Field\FieldFormatter\IconFieldDefault.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal;

/**
 * Plugin implementation of the 'icon_field_default' formatter.
 *
 * @FieldFormatter(
 *  id = "icon_field_default",
 *  label = @Translation("Icon"),
 *  field_types = {
 *    "icon"
 *  }
 * )
 */
class IconFieldDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $entity = $items->getEntity();
    $uri = $entity->uri;
    foreach ($items as $delta => $item) {
      if ($this->getSetting('link_to_content')) {
        $options = array('html' => TRUE, 'attributes' => array());
        if (isset($uri['options']) and !empty($uri['options'])) {
          $options = array_merge($uri['options'], $options);
        }
      }
      else {
        $elements[$delta] = array(
          '#type' => 'icon',
          '#icon' => $item['icon'],
          '#tag' => 'p',
        );
      }
    }

    return $elements;
  }

}
