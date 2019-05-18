<?php

/**
 * @file
 * Contains \Drupal\bideo\Plugin\bideo\Bideo\EmbedCode.
 */

namespace Drupal\bideo\Plugin\Bideo;

use Drupal\bideo\BideoPluginInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Defines an embed code bideo plugin.
 *
 * @Plugin(
 *   id = "embed_code",
 *   title = @Translation("Embed code"),
 *   provider = "bideo"
 * )
 *
 */
class EmbedCode extends PluginBase implements BideoPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->configuration['embed_code'];
  }

}
