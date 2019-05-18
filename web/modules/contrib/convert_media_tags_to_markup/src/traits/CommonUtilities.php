<?php

namespace Drupal\convert_media_tags_to_markup\traits;

use Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup\Entity;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Drupal\file\Entity\File;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Mockable wrapper around decodeEntities().
   */
  public function decodeEntities($text) {
    return Html::decodeEntities($text);
  }

  /**
   * Mockable wrapper around Json::decode().
   */
  public function drupalJsonDecode($tag) {
    return Json::decode($tag);
  }

  /**
   * Mockable wrapper around File::load(). Also throws an exception.
   */
  public function fileLoad($fid) {
    $file = File::load($fid);

    if (!$file) {
      throw new \Exception('Could not load media object');
    }

    return $file;
  }

  /**
   * Get all entities of a specific type and bundle.
   *
   * @param string $type
   *   A type such as node.
   * @param string $bundle
   *   A bundle such as article.
   *
   * @return array
   *   Array of
   *   \Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup\Entity
   *   objects.
   *
   * @throws \Exception
   */
  protected function getAllEntities(string $type, string $bundle) : array {
    $values = [
      'type' => $bundle,
    ];
    $nodes = \Drupal::entityTypeManager()
      ->getListBuilder($type)
      ->getStorage()
      ->loadByProperties($values);
    $return = [];
    foreach ($nodes as $node) {
      $return[] = new Entity($node);
    }
    return $return;
  }

  /**
   * Log a string to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdog(string $string) {
    \Drupal::logger('steward_common')->notice($string);
  }

  /**
   * Log an error to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdogError(string $string) {
    \Drupal::logger('steward_common')->error($string);
  }

  /**
   * Log a \Throwable to the watchdog.
   *
   * @param \Throwable $t
   *   A \throwable.
   */
  public function watchdogThrowable(\Throwable $t, $message = NULL, $variables = array(), $severity = RfcLogLevel::ERROR, $link = NULL) {

    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($t);

    \Drupal::logger('steward_common')->log($severity, $message, $variables);
  }

}
