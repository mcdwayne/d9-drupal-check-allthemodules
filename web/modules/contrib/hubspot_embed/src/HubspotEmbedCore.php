<?php

namespace Drupal\hubspot_embed;

class HubspotEmbedCore {

  /**
   * Finds and returns an embed code by its id.
   *
   * @param $id id in the state array for the embed code.
   * @return mixed HTML embed code or FALSE if none was found.
   */
  public static function getEmbed($id) {
    $stored_embeds = \Drupal::state()->get('hubspot_embed');
    if (isset($stored_embeds[$id])) {
      return $stored_embeds[$id];
    }
    return FALSE;
  }

  /**
   * Find a stored embed code and return its id.
   *
   * @param $embed HTML embed code copied from Hubspot.
   * @return mixed Id for the hubspot code if already known or FALSE if not.
   */
  public static function findEmbed($embed) {
    if (empty($embed)) {
      return FALSE;
    }

    $stored_embeds = \Drupal::state()->get('hubspot_embed');
    foreach ($stored_embeds as $id => $stored_embed) {
      if ($stored_embed == $embed) {
        return $id;
      }
    }
    return FALSE;
  }

  /**
   * @param $embed The embed code to save.
   *
   * @return mixed The integr id for the embed code or FASLE if no embed code was given.
   */
  public static function saveEmbed($embed) {
    if (empty($embed)) {
      return FALSE;
    }

    $exists = self::findEmbed($embed);
    if ($exists) {
      return $exists;
    }
    else {
      $stored_embeds = \Drupal::state()->get('hubspot_embed');
      $stored_embeds[] = $embed;
      \Drupal::state()->set('hubspot_embed', $stored_embeds);
      $cache_backend = \Drupal\Core\Cache\Cache::getBins();
      $cache_backend['bootstrap']->delete('state');
      return max(array_keys($stored_embeds, $embed));
    }
  }

}
