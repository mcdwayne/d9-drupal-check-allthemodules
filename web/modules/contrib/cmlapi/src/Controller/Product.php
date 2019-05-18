<?php

namespace Drupal\cmlapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\Yaml\Yaml;

/**
 * Product Parcer.
 */
class Product extends ControllerBase {

  /**
   * Page export.
   */
  public function page($cml) {
    $cid = $cml;
    $result = __CLASS__;
    $result .= "<br>id={$cid}";
    $data = \Drupal::service('cmlapi.parser_product')->parse($cid);
    if (!empty($data)) {
      $i = 1;
      $max = 330;
      $vids = [];
      $tips = [];
      $izgotovitels = [];
      $result .= '<pre>';
      foreach ($data['data'] as $key => $value) {
        $result .= $i++ . " - {$key}\n";
        $product = $value['product'];
        if (isset($product['ZnaceniaRekvizitov']['ВидНоменклатуры'])) {
          $vid = $product['ZnaceniaRekvizitov']['ВидНоменклатуры'];
          $tip = $product['ZnaceniaRekvizitov']['ТипНоменклатуры'];
          $vids[$vid] = $vid;
          $tips[$tip] = $tip;
          $izgotovitel = $product['Izgotovitel'];
          if (!empty($izgotovitel)) {
            $first_key = key($izgotovitel);
            $izgotovitels[$first_key] = "{$izgotovitel[$first_key]} ($first_key)";
          }
        }
        if ($i < $max || \Drupal::request()->query->get('all') == 'TRUE') {
          foreach ($product as $k => $v) {
            $result .= "    <b> {$k} </b>: ";
            if (!is_array($v)) {
              $result .= "$v\n";
            }
            else {
              if (!empty($v) && array_keys($v)[0] === 0) {
                $result .= json_encode($v, JSON_UNESCAPED_UNICODE) . "\n";
              }
              else {
                $result .= "\n" . Yaml::dump($v, 2);
              }
            }
          }
          $result .= "\n";
          $result .= "Offers:\n";
          $result .= Yaml::dump($value['offers']);
          $result .= "\n\n\n";
        }
        else {
          if ($i == $max) {
            $uri = \Drupal::request()->getRequestUri();
            $all_link = "<a href='$uri?all=TRUE'>Посмотреть всё</a>";
            $result .= "<h2>{$max}+ -- Дальше сокращённый вывод данных [$all_link]</h2>";
          }
          $result .= "    <b>Наименование:</b> {$product['Naimenovanie']}\n";
          if (isset($product['ZnaceniaRekvizitov']['ВидНоменклатуры'])) {
            $result .= "    <b>Вид:</b> {$product['ZnaceniaRekvizitov']['ВидНоменклатуры']}\n";
          }
          if (isset($product['Gruppy'][0])) {
            $result .= "    <b>Gruppy:</b> {$product['Gruppy'][0]}\n";
          }
        }

      }
      $result .= '</pre>';
    }
    return [
      'tip' => [
        '#theme' => 'item_list',
        '#title' => 'ТипНоменклатуры',
        '#items' => $tips,
      ],
      'vid' => [
        '#theme' => 'item_list',
        '#title' => 'ВидНоменклатуры',
        '#items' => $vids,
        '#list_type' => 'ol',
      ],
      'izgotovitel' => [
        '#theme' => 'item_list',
        '#title' => 'Изготовитель',
        '#items' => $izgotovitels,
      ],
      'pre' => ['#markup' => $result],
    ];
  }

}
