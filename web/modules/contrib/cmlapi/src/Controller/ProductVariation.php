<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller routines for page example routes.
 */
class ProductVariation extends ControllerBase {

  /**
   * Page import.
   */
  public function page($cml) {
    $cid = $cml;
    $result = __CLASS__;
    $result .= "<br>id={$cid}";
    $data = \Drupal::service('cmlapi.parser_offers')->parseArray($cid);
    if ($data) {
      $feature = Yaml::dump(self::prepareFeature($data['feature']), 5);
      $offer = Yaml::dump(self::prepareOffer($data['offer'], $data['feature']), 7, 1, FALSE, TRUE);
    }

    return [
      'feature' => ['#markup' => "<pre>$feature</pre>"],
      'category' => ['#markup' => "<pre>$offer</pre>"],
    ];
  }

  /**
   * Json import.
   */
  public function prepareFeature($features) {
    $result = [];
    if (!empty($features)) {
      $features = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($features);
      foreach ($features as $feature) {
        $id = $feature['Ид'];
        $name = $feature['Наименование'];
        if ($feature['ТипЗначений'] == 'Справочник') {
          $sprav = [];
          if (isset($feature['ВариантыЗначений']['Справочник'])) {
            $sprav = \Drupal::service('cmlapi.xml_parser')->xml2Val($feature['ВариантыЗначений']['Справочник']);
          }
          $result['taxonomy']["$name"] = $sprav;
        }
        else {
          $result['field']["$name"] = $feature['Значение'];
        }
      }
    }
    return $result;
  }

  /**
   * Json import.
   */
  public function prepareOffer($offers, $features) {
    $result = [];
    $fields = [];
    $features = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($features);
    foreach ($features as $feature) {
      $id = $feature['Ид'];
      if (!empty($feature['ВариантыЗначений']['Справочник'])) {
        $vocabulary = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($feature['ВариантыЗначений']['Справочник']);
        foreach ($vocabulary as $kay => $value) {
          $fields[$id][$value['ИдЗначения']] = $value['Значение'];
        }
      }
    }
    if (!empty($offers)) {
      if (!empty($offers['Ид'])) {
        $offers = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($offers);
      }
      foreach ($offers as $offer) {
        $id = $offer['Id'];
        $name = $offer['Naimenovanie'];
        $feature = [];
        if (!empty($offer['ZnacheniyaSvoystv'])) {
          foreach ($offer['ZnacheniyaSvoystv'] as $kay => $value) {
            $offer['ZnacheniyaSvoystv'] = $fields[$kay][$value];
          }
        }
        $result[$name] = $offer;
      }
    }
    return $result;
  }

}
