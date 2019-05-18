<?php

namespace Drupal\cloud\Service\Util;

use Drupal\Core\Url;

/**
 * Html generator utility using name for Entity link.
 */
class EntityLinkWithNameHtmlGenerator extends EntityLinkHtmlGenerator {

  /**
   * {@inheritdoc}
   */
  public function generate(Url $url, $id, $name = '', $alt_text = '') {
    if (!empty($name) && $name != $id) {
      $html = $this->linkGenerator->generate($name, $url) . " ($id)";
    }
    else {
      $html = $this->linkGenerator->generate($id, $url);
    }
    return $html;
  }

}
