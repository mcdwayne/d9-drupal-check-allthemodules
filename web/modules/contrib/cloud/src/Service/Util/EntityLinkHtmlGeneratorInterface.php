<?php

namespace Drupal\cloud\Service\Util;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;

/**
 * Html generator utility interface for Entity link.
 */
interface EntityLinkHtmlGeneratorInterface extends ContainerInjectionInterface {

  /**
   * Generate html.
   *
   * @param \Drupal\Core\Url $url
   *   The url of link.
   * @param string $id
   *   The ID.
   * @param string $name
   *   The name.
   * @param string $alt_text
   *   The text of link.
   *
   * @return string
   *   The html of link.
   */
  public function generate(Url $url, $id, $name = '', $alt_text = '');

}
