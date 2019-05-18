<?php

namespace Drupal\cmlapi\Service;

/**
 * Class ParserCatalog.
 */
class ParserCatalog extends ParserBase {

  /**
   * Parse.
   */
  public function parseFlatCatalog($cid = FALSE, $cache_on = TRUE) {
    $rows = FALSE;
    $expire = REQUEST_TIME + 60 * 60 * 24 * 1;
    $uri = $this->cmlService->getFilePath($cid, 'import');
    if ($uri) {
      $rows = &drupal_static("ParserCatalog::getRows():$uri");
      if (!isset($rows)) {
        $cache_key = 'ParserCatalog:' . $uri;
        if (!$cache_on) {
          $cache_key .= rand();
        }
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $rows = $cache->data;
        }
        else {
          if ($uri) {
            $data = $this->getData($uri);
            if (!empty($data)) {
              $rows = $data;
            }
          }
          \Drupal::cache()->set($cache_key, $rows, $expire);
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
    $data = $this->parceXml($xml, TRUE);
    return $data;
  }

  /**
   * Parse.
   */
  public function parse($cid = FALSE) {
    $data = FALSE;
    $uri = $this->cmlService->getFilePath($cid, 'import');
    if ($uri) {
      $this->xmlParserService->parseXmlFile($uri);
      $xml = $this->xmlParserService->xmlString;
      $data = [
        'group' => $this->parceXml($xml, FALSE),
        'feature' => $this->parceFeature($xml),
        'category' => $this->parceCategory($xml),
      ];
    }
    return $data;
  }

  /**
   * Parce.
   */
  public function parceCategory($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('import', 'category');
    return $this->xmlParserService->xmlfind;
  }

  /**
   * Parce.
   */
  public function parceFeature($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('import', 'feature');
    return $this->xmlParserService->xmlfind;
  }

  /**
   * Parce.
   */
  public function parceXml($xml, $flatTree = TRUE) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('import', 'gruppa');
    $tree = $this->xmlParserService->xmlfind;
    if ($flatTree && is_array($tree)) {
      return $this->flatTree($tree);
    }
    return $tree;
  }

  /**
   * Catalog flatTree.
   */
  public function flatTree(array $data, $parentId = NULL, $parent = TRUE) {
    $result = [];
    $i = 0;
    if (!empty($data)) {
      $data = $this->xmlParserService->arrayNormalize($data);
      foreach ($data as $key => $val) {
        $i++;
        $id = $val['Ид'];
        $result[$id] = [
          'id' => $val['Ид'],
          'name' => $val['Наименование'],
          'term_weight' => $i,
          'delete' => isset($val['ПометкаУдаления']) ? $val['ПометкаУдаления'] : FALSE,
        ];
        if ($parentId) {
          $result[$id]['parent'] = $parentId && !$parent ? $parentId : FALSE;
        }
        if (!empty($val['Группы']['Группа'])) {
          $result = array_merge($result, $this->flatTree($val['Группы']['Группа'], $id, FALSE));
        }
      }
    }
    return $result;
  }

}
