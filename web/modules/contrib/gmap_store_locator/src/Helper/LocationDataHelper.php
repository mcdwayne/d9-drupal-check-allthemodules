<?php

namespace Drupal\store_locator\Helper;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Class LocationDataHelper.
 */
class LocationDataHelper {

  /**
   * Get the fields from database.
   *
   * @param array $items
   *   An array containing all the fields used to search the entries in the
   *   'store_locator' table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   */
  public static function getAvailableFields(array $items) {
    $entity_type = $bundle = 'store_locator';
    $get_list = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);

    $available_fields = $filter_array = [];
    if (!empty($items)) {
      foreach ($items as $key => $value) {
        $filter_array[] = $key;
        $available_fields[$key][$key] = $get_list[$key]->getLabel();
        $available_fields[$key]['#weight'] = $value[$key];
      }

      foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && !in_array($field_name, $filter_array)) {
          $available_fields[$field_name][$field_name] = $get_list[$field_name]->getLabel();
          $available_fields[$field_name]['#weight'] = 0;
        }
      }
    }
    else {
      $filter_items = [
        'id',
        'uuid',
        'langcode',
        'user_id',
        'status',
        'latitude',
        'longitude',
        'created',
        'changed',
      ];
      foreach ($get_list as $key => $value) {
        if (!in_array($key, $filter_items)) {
          $available_fields[$key][$key] = $value->getLabel();
        }
      }
    }
    return $available_fields;
  }

  /**
   * Load the data in list & infowindow.
   *
   * @param string $type
   *   Available types 'infowindow' & 'list'
   *   Get all the associated values from the 'store_locator' table.
   *
   * @return object
   *   An object containing the field information.
   */
  public static function loadInfowindow($type = NULL) {
    $location_data = $item_list = $filter_array = [];
    $entity_type = $bundle = 'store_locator';
    $results = \Drupal::entityTypeManager()->getStorage('store_locator')->loadByProperties(['status' => 1]);
    $field_type = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    $extra_field_type = ['email', 'telephone'];
    $api_key = \Drupal::config('store_locator.settings')->get($type);

    foreach ($results as $value) {
      foreach ($api_key as $k => $v) {
        if ($v[$k] == 0) {
          unset($api_key[$k]);
        }
        else {
          $filter_array[] = $k;
          $location_data[$k] = $value->get($k)->value;
        }
      }

      if (isset($api_key['logo']['logo']) && !empty($api_key['logo']['logo'])) {
        $location_data['logo'] = '';
        if (!empty($value->get('logo')->target_id)) {
          $file = File::load($value->get('logo')->target_id);
          $style = \Drupal::config('store_locator.settings')->get('logo_style');
          $file_path = ImageStyle::load($style)->buildUrl($file->getFileUri());
          $location_data['logo'] = '<img src="' . $file_path . '">';
        }
      }
      $location_data['latitude'] = $value->get('latitude')->value;
      $location_data['longitude'] = $value->get('longitude')->value;
      $geo_perm = $value->get('address_one')->value . ' ' . $value->get('postcode')->value . ' ' . $value->get('city')->value;
      $location_data['get_direction'] = "<a href=https://maps.google.com?q='" . preg_replace('/\s+/', '+', $geo_perm) . " target='_blank''>" . t('Get Direction') . "</a></span>";

      foreach ($field_type as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && in_array($field_name, $filter_array)) {
          if (in_array($field_definition->getType(), $extra_field_type)) {
            $target = '';
            if ($field_definition->getType() == 'telephone' && !empty($value->get($field_name)->value)) {
              $target = '<a href="tel:' . $value->get($field_name)->value . '">' . $value->get($field_name)->value . '</a>';
            }
            if ($field_definition->getType() == 'email' && !empty($value->get($field_name)->value)) {
              $target = '<a href="mailto:' . $value->get($field_name)->value . '">' . $value->get($field_name)->value . '</a>';
            }
            $location_data[$field_name] = $target;
          }
        }
      }
      $item_list[] = $location_data;
    }
    if ($type == 'infowindow') {
      $fid = \Drupal::config('store_locator.settings')->get('marker');
      if (!empty($fid)) {
        $file = File::load($fid);
        $path = $file->getFileUri();
        $marker_path = Url::fromUri(file_create_url($path))->toString();
      }
    }

    return ($type == 'infowindow') ? [
      'marker' => ['icon' => $marker_path],
      'itemlist' => $item_list,
    ] : ['itemlist' => $item_list];
  }

  /**
   * Get the Available Image Style.
   *
   * @return array
   *   An Array Contains available image styles.
   */
  public static function getAvailableStyle() {
    $styles = ImageStyle::loadMultiple();
    $available_style = [];
    foreach ($styles as $value) {
      $available_style[$value->get('name')] = $value->get('label');
    }
    return $available_style;
  }

}
