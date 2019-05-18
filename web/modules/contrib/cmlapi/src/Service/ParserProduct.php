<?php

namespace Drupal\cmlapi\Service;

use Drupal\Component\Transliteration\PhpTransliteration;

/**
 * Class ParserProduct.
 */
class ParserProduct extends ParserBase {

  /**
   * Parse.
   */
  public function parse($cid = FALSE, $cache_on = TRUE) {
    $size = 300;
    $expire = REQUEST_TIME + 60 * 60 * 24 * 1;
    $rows = FALSE;
    $uri = $this->cmlService->getFilePath($cid, 'import');
    if ($uri) {
      $rows = &drupal_static("ParserProduct::parse():$uri");
      if (!isset($rows)) {
        $cache_key = 'ParserProduct:' . $uri;
        if (!$cache_on) {
          $cache_key .= rand();
        }
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $rows = [
            'info' => \Drupal::cache()->get("$cache_key::info")->data,
            'data' => [],
          ];
          if (is_numeric($cache->data)) {
            $chunks = intdiv($cache->data, $size);
            for ($i = 0; $i <= $chunks; $i++) {
              $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
              $rows['data'] = array_merge($rows['data'], $chunk);
            }
          }
        }
        else {
          if ($uri) {
            $data = $this->getData($uri);
            if (!empty($data)) {
              $rows = $data;
            }
          }
          \Drupal::cache()->set("$cache_key::info", $rows['info'], $expire);
          if (isset($rows['data'])) {
            $count = count($rows['data']);
            \Drupal::cache()->set($cache_key, $count, $expire);
            $chunks = array_chunk($rows['data'], $size, TRUE);
            foreach ($chunks as $i => $chunk) {
              \Drupal::cache()->set("$cache_key::data::$i", $chunk, $expire);
            }
          }
        }
      }
    }
    return $rows;
  }

  /**
   * Get Data.
   */
  public function getData($uri) {
    $this->xmlParserService->parseXmlFile($uri);
    $xml = $this->xmlParserService->xmlString;
    $data = $this->parseXml($xml);
    return $data;
  }

  /**
   * Parse XML.
   */
  public function parseXml($xml) {
    $config = \Drupal::config('cmlapi.mapsettings');
    $trans = new PhpTransliteration();
    $map = $this->map('tovar-standart', 'tovar-dop');
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('import', 'product');
    $products = $this->xmlParserService->xmlfind;
    $products = $this->xmlParserService->arrayNormalize($products);

    $result = [];
    if (!empty($this->xmlParserService->xmlArray['Классификатор']['Свойства']['Свойство'])) {
      $result['info']['props'] = $this->xmlParserService->xmlArray['Классификатор']['Свойства']['Свойство'];
    }
    if ($products) {
      foreach ($products as $products1c) {
        $key = $products1c['Ид'];
        $id = strstr("{$key}#", "#", TRUE);
        if (!empty($products1c['@attributes']['Статус'])) {
          $result['data'][$id]['product']['status'] = $products1c['@attributes']['Статус'];
        }
        foreach ($map as $map_key => $map_info) {
          $name = $trans->transliterate($map_key, '');
          if (!isset($result['data'][$id]['offers'][$key])) {
            $result['data'][$id]['offers'][$key] = [];
          }
          if (isset($map_info['dst']) && $map_info['dst'] == 'offers') {
            $result['data'][$id]['offers'][$key][$name] = $this->xmlParserService->prepare($products1c, $map_key, $map_info);
          }
          else {
            $result['data'][$id]['product'][$name] = $this->xmlParserService->prepare($products1c, $map_key, $map_info);
          }
        }
      }
    }
    return $result;
  }

}
