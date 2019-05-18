<?php

namespace Drupal\rocket_chat;

/**
 * Copyright (c) 2016.
 *
 * Authors:
 * - Lawri van Buël <sysosmaster@2588960.no-reply.drupal.org>.
 *
 * This file is part of (rocket_chat) a Drupal 8 Module for Rocket.Chat
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * @file
 * Contains \Drupal\rocket_chat\FormManager.
 */

/**
 * Check the form values.
 */
class Utility {

  /**
   * ServerRun.
   *
   * @param string $url
   *   Url to use.
   *
   * @return bool
   *   Connection Worked?
   */
  public static function serverRun($url) {
    $urlSplit = Utility::parseUrl($url);
    try {
      if ($ping = fsockopen($urlSplit['url'], $urlSplit['port'], $errCode, $errStr, 10)) {
        fclose($ping);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $exception) {
      error_log("serverRun encountered and exception, check [$url] for valid URL");
      return FALSE;
    }
  }

  /**
   * Helper function to split an URL into its base components.
   *
   * @param string $url
   *   Url to parse.
   *
   * @return array
   *   Url in its separated Parts.
   *
   * @throws \HttpUrlException
   *   Throws when scheme is missing.
   */
  public static function parseUrl($url) {
    $returnValue = parse_url($url);
    if (!isset($returnValue['scheme'])) {
      throw new \HttpUrlException("Missing Scheme.", 404);
    }
    if (!isset($returnValue['host'])) {
      $returnValue['hosts'] = 'localhost';
    }
    if (!isset($returnValue['path'])) {
      $returnValue['path'] = "";
    }
    if (!isset($returnValue['port'])) {
      switch ($returnValue['scheme']) {
        case "http":
          $returnValue['port'] = 80;
          break;

        case "https":
          $returnValue['port'] = 443;
          break;

      }
    }
    $returnValue['baseUrl'] = $returnValue['host'] . $returnValue['path'];
    switch ($returnValue['scheme']) {
      default:
        $returnValue['url'] = "tcp://" . $returnValue['baseUrl'];
        break;

      case "https":
        $returnValue['url'] = "tls://" . $returnValue['baseUrl'];
        break;

    }
    return $returnValue;
  }

}
