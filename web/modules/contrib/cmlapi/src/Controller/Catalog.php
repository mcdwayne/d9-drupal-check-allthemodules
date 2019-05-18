<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller routines for page example routes.
 */
class Catalog extends ControllerBase {

  /**
   * Page tree.
   */
  public function page($cml) {
    $cid = $cml;
    $result = '';
    $data = \Drupal::service('cmlapi.parser_catalog')->parse($cid);
    if ($data) {
      $result .= '<div id="jstree">';
      $result .= $this->renderGroups($data['group'], TRUE);
      $result .= '</div>';
      $feature = Yaml::dump(self::prepareFeature($data['feature']), 5);
      $category = Yaml::dump(self::prepareCategory($data['category'], $data['feature']), 7, 1, FALSE, TRUE);
    }

    return [
      'tree' => [
        '#markup' => $result,
        '#attached' => ['library' => ['cmlapi/cmlapi.jstree']],
      ],
      'category' => ['#markup' => "<pre>$category</pre>"],
      //'feature' => ['#markup' => "<pre>$feature</pre>"],
    ];
  }

  /**
   * Json import.
   */
  public function prepareCategory($categorys, $features) {
    $result = [];
    $fields = [];
    $features = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($features);
    foreach ($features as $feature) {
      $id = $feature['Ид'];
      $fields[$id] = $feature;
    }
    if (!empty($categorys)) {
      $categorys = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($categorys);
      foreach ($categorys as $category) {
        $id = $category['Ид'];
        $name = $category['Наименование'];
        $feature = [];
        if (!empty($category['Свойства']['Ид'])) {
          $category['Свойства']['Ид'] = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($category['Свойства']['Ид']);
          foreach ($category['Свойства']['Ид'] as $value) {
            $feature[] = self::prepareFeature($fields[$value]);
          }
        }
        $result["<b>$name</b>"] = $feature;
      }
    }
    return $result;
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
          $result['field']["$name"] = $feature['ТипЗначений'];
        }
      }
    }
    return $result;
  }

  /**
   * Render.
   */
  public function renderGroups($groups, $parent = FALSE) {
    $output = '<ul>';

    if (!empty($groups)) {
      $groups = \Drupal::service('cmlapi.xml_parser')->arrayNormalize($groups);

      foreach ($groups as $group) {
        $data = FALSE;
        if ($parent) {
          $data = " data-jstree='{ \"opened\" : true }' ";
        }
        $output .= '<li ' . $data . '>';
        $output .= $group['Наименование'];
        if (!empty($group['Группы']['Группа'])) {
          $output .= self::renderGroups($group['Группы']['Группа']);
        }
        $output .= '</li>';
      }
      $output .= '</ul>';
    }
    return $output;
  }

}
