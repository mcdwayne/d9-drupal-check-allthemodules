<?php

namespace Drupal\cloud\Service\Util;

use Drupal\Core\Url;

/**
 * Html generator utility using short name for Entity link.
 */
class EntityLinkWithShortNameHtmlGenerator extends EntityLinkHtmlGenerator {

  /**
   * {@inheritdoc}
   */
  public function generate(Url $url, $id, $name = '', $alt_text = '') {
    $text = $id;
    if (!empty($name) && $name != $id) {
      $text = $name;
    }

    return $this->linkGenerator->generate($text, $url);
  }

}
