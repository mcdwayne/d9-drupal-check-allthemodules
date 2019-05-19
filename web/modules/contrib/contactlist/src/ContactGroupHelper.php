<?php

namespace Drupal\contactlist;

use Drupal\Component\Utility\Tags;

class ContactGroupHelper {

  /**
   * Returns a comma-separated string of the contact group names.
   *
   * @param \Drupal\contactlist\Entity\ContactGroupInterface[] $groups
   *
   * @return string
   */
  public static function viewAsTags(array $groups) {
    return Tags::implode(array_map(function($group) {
      return $group->getName();
    }, $groups));
  }

  /**
   * Loads contact groups from a list of tags.
   *
   * @param string $tags
   *
   * @return \Drupal\contactlist\Entity\ContactGroupInterface[]
   *   The groups that correspond to the tags.
   *
   * @todo How do we handle non-existent tags.
   */
  public static function getFromTags($tags) {
    $tags = Tags::explode($tags);

  }

}
