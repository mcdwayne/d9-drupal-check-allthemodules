<?php

namespace Drupal\amp_video_embed_field_formatter\Element;

use Drupal\video_embed_field\Element\VideoEmbedIFrame;

/**
 * Providers an element design for embedding video iframes in AMP.
 *
 * @RenderElement("amp_video_embed_iframe")
 */
class AmpVideoEmbedIFrame extends VideoEmbedIFrame {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme'] = 'amp_video_embed_iframe';
    $info['#video_embed_id'] = '';
    return $info;
  }

}
