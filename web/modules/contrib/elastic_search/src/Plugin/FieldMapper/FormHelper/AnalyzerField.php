<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 09.02.17
 * Time: 13:12
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Class AnalyzerTrait
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 */
trait AnalyzerField {

  use AbstractTranslation;

  /**
   * @var string
   */
  public static $NONE_IDENTIFIER = '--none--';

  /**
   * @return string
   */
  protected function getAnalyzerFieldDefault(): string {
    return self::$NONE_IDENTIFIER;
  }

  /**
   * @param \Drupal\Core\Entity\EntityStorageInterface $analyzerStorage
   *
   * @return array
   */
  protected function getAnalyzerOptions(EntityStorageInterface $analyzerStorage) {
    $options = array_keys($analyzerStorage->loadMultiple());
    $options = array_combine($options, $options);
    return array_merge([
                         self::$NONE_IDENTIFIER => self::$NONE_IDENTIFIER,
                       ],
                       $options);
  }

}