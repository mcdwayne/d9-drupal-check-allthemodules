<?php

namespace Drupal\cmlapi\Service;

use Drupal\Component\Transliteration\PhpTransliteration;

/**
 * Class ParserOffers.
 */
class ParserOffers extends ParserBase {

  /**
   * Parse.
   */
  public function parse($cid = FALSE, $cache_on = TRUE) {
    $size = 300;
    $expire = REQUEST_TIME + 60 * 60 * 24 * 1;
    $rows = FALSE;
    $uri = $this->cmlService->getFilePath($cid, 'offers');
    if ($uri) {
      $rows = &drupal_static("ParserOffers::parse():$uri");
      if (!isset($rows)) {
        $cache_key = 'ParserOffers:' . $uri;
        if (!$cache_on) {
          $cache_key .= rand();
        }
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $rows = [];
          if (is_numeric($cache->data)) {
            $chunks = intdiv($cache->data, $size);
            for ($i = 0; $i <= $chunks; $i++) {
              $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
              $rows = array_merge($rows, $chunk);
            }
          }
        }
        else {
          if ($uri) {
            $data = $this->getData($uri);
            if (!empty($data['offer'])) {
              $rows = $data['offer'];
            }
          }
          if (isset($rows)) {
            $count = count($rows);
            \Drupal::cache()->set($cache_key, $count, $expire);
            $chunks = array_chunk($rows, $size);
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
   * Parse.
   */
  public function parseArray($cid = FALSE, $cache_on = TRUE) {
    $size = 300;
    $expire = REQUEST_TIME + 60 * 60 * 24 * 1;
    $rows = FALSE;
    $uri = $this->cmlService->getFilePath($cid, 'offers');
    if ($uri) {
      $rows = &drupal_static("ParserOffers::parse():$uri");
      if (!isset($rows)) {
        $cache_key = 'ParserOffers:' . $uri;
        if (!$cache_on) {
          $cache_key .= rand();
        }
        if ($cache = \Drupal::cache()->get($cache_key)) {
          $rows = [];
          if (is_numeric($cache->data)) {
            $chunks = intdiv($cache->data, $size);
            for ($i = 0; $i <= $chunks; $i++) {
              $chunk = \Drupal::cache()->get("$cache_key::data::$i")->data;
              $rows = array_merge($rows, $chunk);
            }
            $arr = $rows;
            $rows = [];
            $rows['offer'] = $arr;
            $m = \Drupal::cache()->get("$cache_key::data::feature");
            $rows['feature'] = \Drupal::cache()->get("$cache_key::data::feature")->data;
          }
        }
        else {
          if ($uri) {
            $data = $this->getData($uri);
            if (!empty($data)) {
              $rows = $data;
            }
          }
          if (isset($rows['offer'])) {
            $count = count($rows['offer']);
            \Drupal::cache()->set($cache_key, $count, $expire);
            $chunks = array_chunk($rows['offer'], $size);
            foreach ($chunks as $i => $chunk) {
              \Drupal::cache()->set("$cache_key::data::$i", $chunk, $expire);
            }
          }
          if (isset($rows['feature'])) {
            \Drupal::cache()->set("$cache_key::data::feature", $rows['feature'], $expire);
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
   * Parse.
   */
  public function parseXml($xml) {
    $config = \Drupal::config('cmlapi.mapsettings');
    $trans = \Drupal::transliteration();
    $map = $this->map('offers-standart', 'offers-dop');

    $xml = $this->xmlParserService->xmlString;
    $data = [
      'feature' => $this->parceFeature($xml),
      'offer' => [],
    ];
    $offers = $this->parceOffer($xml);
    if ($offers) {
      if (isset($offers['Ид'])) {
        $offers = [$offers];
      }
      foreach ($offers as $offer1c) {
        $offer = [];
        foreach ($map as $map_key => $map_info) {
          $name = $trans->transliterate($map_key, '');
          $offer[$name] = $this->xmlParserService->prepare($offer1c, $map_key, $map_info);
        }
        $id = $offer1c['Ид'];
        $data['offer'][$id] = $offer;
      }
    }
    return $data;
  }

  /**
   * Parce.
   */
  public function parceFeature($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('offers', 'feature');
    return $this->xmlParserService->xmlfind;
  }

  /**
   * Parce.
   */
  public function parceOffer($xml) {
    $this->xmlParserService->parseXmlString($xml);
    $this->xmlParserService->get('offers', 'offer');
    return $this->xmlParserService->xmlfind;
  }

  /**
   * Parse.
   */
  // public function parseXml($xml) {
  //   $config = \Drupal::config('cmlapi.mapsettings');
  //   $trans = new PhpTransliteration();
  //   $map = $this->map('offers-standart', 'offers-dop');
  //
  //   $this->xmlParserService->parseXmlString($xml);
  //   $this->xmlParserService->get('offers', 'offer');
  //   $offers = $this->xmlParserService->xmlfind;
  //
  //   $result = [];
  //   if ($offers) {
  //     if (isset($offers['Ид'])) {
  //       $offers = [$offers];
  //     }
  //     foreach ($offers as $offer1c) {
  //       $offer = [];
  //       foreach ($map as $map_key => $map_info) {
  //         $name = $trans->transliterate($map_key, '');
  //         $offer[$name] = $this->xmlParserService->prepare($offer1c, $map_key, $map_info);
  //       }
  //       $id = $offer1c['Ид'];
  //       $result[$id] = $offer;
  //
  //     }
  //   }
  //   return $result;
  // }

}
