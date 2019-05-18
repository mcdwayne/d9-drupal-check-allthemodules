<?php

namespace Drupal\advertising_products;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Render\Renderer;
use Drupal\image\Entity\ImageStyle;

class AdvertisingProductMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher {

  /**
   * Adds primary image and alternative images to the matches.
   *
   * Most of this function is a 1:1 copy of EntityAutocomplete::getMatches
   * from core. We can't call it as parent as it doesn't return the IDs in a
   * convenient format.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        $entity_ids = array_keys($values);
        $entities = entity_load_multiple('advertising_product', $entity_ids);
        foreach ($values as $entity_id => $label) {
          $alternativ_images = array();
          $entity = $entities[$entity_id];
          if (isset($entity->extra_images)) {
            $alternativ_images = array_values($entity->extra_images);
          }
          $file = $entity->product_image->get(0)->entity;
          $uri = $file->getFileUri();
          $full_url = ImageStyle::load('thumbnail')->buildUrl($uri);

          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = [
            'value' => $key,
            'label' => $label,
            'primary' => $full_url,
            'alternatives' => $alternativ_images,
          ];
        }
      }
    }

    return $matches;
  }

}
