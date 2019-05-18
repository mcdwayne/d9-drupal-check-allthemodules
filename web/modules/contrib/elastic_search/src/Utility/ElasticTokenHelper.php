<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 13:36
 */

namespace Drupal\elastic_search\Utility;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class ElasticTokenHelper
 *
 * @package Drupal\elastic_search\Utility
 */
class ElasticTokenHelper {

  /**
   * Base ID for all tokens
   */
  const TOKEN_TYPE_KEY = 'elastic_search';
  /**
   * Id of the array key we use to look for language
   */
  const LANGUAGE_ID_KEY = 'lang';

  /**
   * An arrray of all tokens provided by the elastic_search module
   *
   * @var array
   */
  private static $tokenIds = [
    'language_derived_analyzer',
  ];

  /**
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  public static function getTokenInfo(TranslationInterface $stringTranslation) {
    $info = [];
    //Elastic tokens
    $info['tokens']['elastic_search']['language_derived_analyzer'] = [
      new TranslatableMarkup('A token used to create language derived analyzer settings for indices',
                             [],
                             [],
                             $stringTranslation),
    ];
    return $info;
  }

  /**
   * @param string                                 $type
   * @param array                                  $tokens
   * @param array                                  $data
   * @param array                                  $options
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleableMetadata
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  public static function doTokenReplacement(string $type,
                                            array $tokens,
                                            array $data,
                                            array $options,
                                            BubbleableMetadata $bubbleableMetadata) {
    $replacements = [];
    if ($type === self::TOKEN_TYPE_KEY) {
      foreach ($tokens as $name => $original) {
        // Find the desired token by name
        switch ($name) {
          case self::$tokenIds[0]:
            //'language_derived_analyzer'
            $replacements[$original] = self::languageDerivedAnalyzerReplacement($data);
            break;
        }
      }
    }
    // Return the replacements.
    return $replacements;

  }

  /**
   * @param array $data
   *
   * @return mixed
   * @throws \InvalidArgumentException
   */
  private static function languageDerivedAnalyzerReplacement(array $data) {
    if (!array_key_exists(self::LANGUAGE_ID_KEY, $data)) {
      throw new \InvalidArgumentException('No language key found');
    }
    return DrupalLangCodeToElasticAnalyzer::getLanguageAnalyzer($data[self::LANGUAGE_ID_KEY]);
  }

}