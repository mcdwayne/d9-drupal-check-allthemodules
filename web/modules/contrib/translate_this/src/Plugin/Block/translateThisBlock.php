<?php

namespace Drupal\translate_this\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a translate this block.
 *
 * @Block(
 *   id = "translate_this_block",
 *   admin_label = @Translation("TranslateThis block"),
 * )
 */
class TranslateThisBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromUri('http://translateth.is/', array(
      'attributes' => array(
        'class' => array(
          'translate-this-button',
        ),
      ),
    ));
    $content = '<div id="translate-this">' . \Drupal::l(t('Translate'), $url) . '</div>';
    $content .= '<script>TranslateThis({' . translate_this_js_options() . '});</script>';

    return array(
      '#children' => $content,
      '#attached' => array(
        'library' => array(
          'translate_this/translate_this',
        ),
      ),
      '#cache' => array(
        'tags' => array('config:translate_this.settings'),
      ),
    );
  }

}
