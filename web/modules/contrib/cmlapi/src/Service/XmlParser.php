<?php

namespace Drupal\cmlapi\Service;

/**
 * Class XmlParser.
 */
class XmlParser implements XmlParserInterface {
  /**
   * Props.
   *
   * @var xmlString
   *      stages
   *      xmlImportMapping import.xml
   *      xmlOffersMapping offers.xml
   */
  public $xmlString = NULL;
  public $xmlArray = [0 => []];
  public $xmlfind = FALSE;
  public $stages = [
    'category',
    'feature',
    'product',
    'price',
    'sku',
    'stock',
    'offer',
    'order',
    'image',
  ];
  public $xmlImportMapping = [
    'gruppa'   => 'Классификатор/Группы/Группа',
    'category' => 'Классификатор/Категории/Категория',
    'feature'  => 'Классификатор/Свойства/Свойство',
    'price'    => 'Классификатор/ТипыЦен',
    'stock'    => 'Классификатор/Склады',
    'product'  => 'Каталог/Товары/Товар',
  ];
  public $xmlOffersMapping = [
    'price'   => 'ПакетПредложений/ТипыЦен',
    'stock'   => 'ПакетПредложений/Склады',
    //'feature' => 'ПакетПредложений/Свойства',
    'feature' => 'Классификатор/Свойства/Свойство',
    'offer'   => 'ПакетПредложений/Предложения/Предложение',
  ];
  public $productMaps = [
    'ЗначенияРеквизитов'   => 'ЗначениеРеквизита',
    'ЗначенияСвойств'      => 'ЗначенияСвойства',
    'ХарактеристикиТовара' => 'ХарактеристикаТовара',
    'СтавкиНалогов'        => 'СтавкаНалога',
    'Цены'                 => 'Цена',
    'Изготовитель'         => '',
  ];

  /**
   * Constructs a new XmlParser object.
   */
  public function __construct() {

  }

  /**
   * Get Last.
   */
  public function xmlString($uri) {
    $filepath = drupal_realpath($uri);
    return $filepath;
  }

  /**
   * Parse_xml_file.
   */
  public function parseXmlFile($file_uri) {
    $filepath = drupal_realpath($file_uri);
    $this->xmlString = NULL;

    if (is_file($filepath) && is_readable($filepath)) {
      $my_file = fopen($filepath, "r");
      while ($my_xml_input = fread($my_file, filesize($filepath))) {
        $this->xmlString .= $my_xml_input;
      }
      fclose($my_file);

    }
    else {
      trigger_error("supplied argument is not a URI to a (readable) file", E_USER_ERROR);
    }

  }

  /**
   * Find.
   */
  public function prepare($data, $key, $map) {
    $result = NULL;
    // Skip.
    if (isset($map['skip']) && $map['skip']) {
      return $result;
    }
    // Parse.
    if (isset($data[$key]) && $data[$key] !== NULL) {
      $field = $data[$key];
      $type = $this->prepareType($map);
      if ($type == 'string') {
        $result = $this->prepareString($field);
      }
      elseif ($type == 'array') {
        $result = $this->prepareArray($field, $key);
      }
      elseif ($type == 'keyval') {
        $result = $this->prepareKeyVal($field, $key);
      }
      elseif ($type == 'attr') {
        $result = $this->prepareAttribute($field, $map);
      }
    }

    return $result;
  }

  /**
   * Prepare String.
   */
  public function prepareKeyVal($field, $key) {
    $result = [];
    if (isset($this->productMaps[$key])) {
      if ($m = $this->productMaps[$key]) {
        $result = self::xml2KeyVal($field[$m]);
      }
      else {
        $result = self::xml2KeyVal($field);
      }
    }
    return $result;
  }

  /**
   * Conv 1c data to key-val.
   */
  public static function xml2KeyVal($arr) {
    $result = [];
    if (!empty($arr)) {
      foreach (self::arrayNormalize($arr) as $value) {
        $dat = array_values($value);
        if (count($dat) == 2) {
          $result[$dat[0]] = $dat[1];
        }
      }
    }
    return $result;
  }

  /**
   * Conv 1c data to key-val.
   */
  public static function xml2Val($arr) {
    $result = [];
    if (!empty($arr)) {
      foreach (self::arrayNormalize($arr) as $value) {
        $dat = array_values($value);
        if (count($dat) == 2) {
          $result[] = $dat[1];
        }
      }
    }
    return $result;
  }

  /**
   * Prepare String.
   */
  public function prepareType($map) {
    $type = 'string';
    if (isset($map['type'])) {
      $type = $map['type'];
      if (is_array($type)) {
        $type = 'array';
      }
    }
    return $type;
  }

  /**
   * Prepare String.
   */
  public function prepareString($field) {
    $result = '';
    if (!is_array($field)) {
      $result = $field;
    }
    return $result;
  }

  /**
   * Prepare String.
   */
  public function prepareAttribute($field, $map) {
    $result = '';
    if (isset($map['attr'])) {
      $attr = $map['attr'];
      if (isset($field['@attributes'][$attr])) {
        $result = $field['@attributes'][$attr];
      }
    }
    return $result;
  }

  /**
   * Prepare Array.
   */
  public function prepareArray($field, $key) {
    $result = [];
    if (isset($this->productMaps[$key]) && $m = $this->productMaps[$key]) {
      $result = $this->arrayNormalize($field[$m]);
    }
    elseif ($key == 'Группы') {
      foreach ($this->arrayNormalize($field) as $group) {
        $result[] = $group['Ид'];
      }
    }
    else {
      if (isset($map['type']['inside'])) {
        $result = $this->arrayNormalize($field[$map['type']['inside']]);
      }
      else {
        $result = $this->arrayNormalize($field);
      }
      // Выводим только значения определнного поля.
      if (isset($map['type']['list'])) {
        $buffer = [];
        foreach ($result as $index => $row) {
          if (isset($row[$map['type']['list']])) {
            $buffer[] = $row[$map['type']['list']];
          }
        }
        if (count($result) == count($buffer)) {
          $result = $buffer;
        }
        else {
          $result = NULL;
        }
      }
      // Преобразуем в json.
      if (isset($map['type']['json']) && $jsonStatement = $map['type']['json']) {
        $result = json_encode($result, JSON_UNESCAPED_UNICODE);
      }
    }
    return $result;
  }

  /**
   * Find.
   */
  public function find($map) {
    $query = explode("/", $map);
    $result = FALSE;
    $this->xmlfind = $this->xmlArray;
    foreach ($query as $q) {
      if (isset($this->xmlfind[$q])) {
        $this->xmlfind = $this->xmlfind[$q];
      }
      else {
        $this->xmlfind = FALSE;
      }
    }
    return $result;
  }

  /**
   * Find.
   */
  public function get($type, $key) {
    $map = FALSE;
    if ($type == 'import') {
      $mapping = $this->xmlImportMapping;
    }
    elseif ($type == 'offers') {
      $mapping = $this->xmlOffersMapping;
    }
    if (isset($mapping[$key])) {
      $map = $mapping[$key];
      $this->queryMap = $map;
    }

    $this->find($map);
    return $map;
  }

  /**
   * Parse_xml_file.
   */
  public function parseXmlString($xml_string) {
    $xml = simplexml_load_string($xml_string);
    if (!$xml) {
      trigger_error("data can not be parsed", E_USER_ERROR);
    }
    $json = json_encode($xml, JSON_FORCE_OBJECT);
    $this->xmlArray = json_decode($json, TRUE);
    $this->xmlString = 'parse DONE && string remove';
    return $this->xmlArray;
  }

  /**
   * HELPER: Array Normalize.
   */
  public static function mapMerge($array1, $array2) {
    if (!is_array($array1)) {
      $array1 = [];
    }
    if (!is_array($array2)) {
      $array2 = [];
    }
    $map = array_merge($array1, $array2);
    return $map;
  }

  /**
   * HELPER: Array Normalize.
   */
  public static function arrayNormalize($array) {
    $norm = FALSE;
    if (is_string($array)) {
      $norm = FALSE;
    }
    else {
      foreach ($array as $key => $value) {
        if (is_numeric($key)) {
          $norm = TRUE;
        }
      }
    }

    if ($norm) {
      return $array;
    }
    else {
      return [$array];
    }
  }

}
