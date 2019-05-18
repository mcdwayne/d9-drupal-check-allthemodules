<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 09/12/16
 * Time: 00:32
 */

namespace Drupal\elastic_search\Utility;

class DrupalLangCodeToElasticAnalyzer {

  /**
   * Get the name of the language analyzer to be used for a given language code.
   *
   * @param string $langcode
   *
   * @return string
   */
  public static function getLanguageAnalyzer(string $langcode) {
    return self::$language_analyzers[$langcode] ?? self::$default;
  }

  private static $default = 'standard';

  private static $language_analyzers = [
    // Use one of the built-in language analysers for 5.2
    // https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
    'ar' => 'arabic',

    'bg' => 'bulgarian',

    'ca' => 'catalan',
    'cz' => 'czech',

    'da' => 'danish',
    'de' => 'german',

    'en'          => 'english',
    'en-x-simple' => 'english',
    'es'          => 'spanish',
    'el'          => 'greek',
    'eu'          => 'basque',

    'fa' => 'persian',
    'fi' => 'finnish',
    'fr' => 'french',

    'ga' => 'irish',
    'gl' => 'galician',

    'hi' => 'hindi',
    'hu' => 'hungarian',
    'hy' => 'armenian',

    'id' => 'indonesian',
    'it' => 'italian',

    'ku' => 'sorani',

    'lt' => 'lithuanian',
    'lv' => 'latvian',

    'nl' => 'dutch',
    'nb' => 'norwegian',
    'nn' => 'norwegian',

    'pt-br' => 'brazilian',
    'pt-pt' => 'portugese',

    'ro' => 'romanian',
    'ru' => 'russian',

    'sv' => 'swedish',

    'th'      => 'thai',
    'tr'      => 'turkish',

    // Chinese, install the analysis-smartcn elasticsearch plugin.
    // 'zh-hans' => 'smartcn',
    'zh-hans' => 'cjk',

    // Japanese, install the analysis-kuromoji elasticsearch plugin.
    //'ja'      => 'kuromoji',
    'ja'      => 'cjk',
  ];

}