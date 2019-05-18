<?php

namespace Drupal\cmlapi\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ParserBase.
 */
class ParserBase {

  /**
   * Constructs a new ParserBase object.
   *
   * @param \Drupal\cmlapi\Service\CmlServiceInterface $cml
   *   The alias cleaner.
   * @param \Drupal\cmlapi\Service\XmlParserInterface $xml_parser
   *   The alias storage helper.
   */
  public function __construct(CmlServiceInterface $cml, XmlParserInterface $xml_parser) {
    $this->cmlService = $cml;
    $this->xmlParserService = $xml_parser;
  }

  /**
   * Map.
   */
  public function map($set1, $set2) {
    $config = \Drupal::config('cmlapi.mapsettings');
    $map_sdandart = Yaml::parse($config->get($set1));
    $map_dop = Yaml::parse($config->get($set2));
    if (is_array($map_dop)) {
      $map = array_merge($map_sdandart, $map_dop);
    }
    else {
      $map = $map_sdandart;
    }
    return $map;
  }

}
