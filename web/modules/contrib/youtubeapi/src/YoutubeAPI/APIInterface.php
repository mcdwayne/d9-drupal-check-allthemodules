<?php
/**
 * Created by PhpStorm.
 * User: me
 * Date: 8/23/16
 * Time: 11:44 PM
 */

namespace Drupal\youtubeapi\YoutubeAPI;


interface APIInterface {

  /**
   * Returns API method.
   *
   * @return String
   *   API method.
   */
  public function getUrl();

  /**
   * Returns API Url.
   *
   * @return String
   *   API Url.
   */
  public static function getMethod();


  /**
   * Returns all available Parameters.
   *
   * @return Array
   *   All available Parameters.
   * @throws \Masterminds\HTML5\Exception
   */
  public function getParameters();

  /**
   * Returns all Mandatory Parameters.
   *
   * @return Array
   *   Mandatory Parameters.
   */
  public function getMandatoryParameters();
}
