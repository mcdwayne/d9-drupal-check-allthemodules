<?php

namespace Drupal\getresponse\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'GetResponse' block.
 *
 * @Block(
 *   id = "getresponse_block",
 *   admin_label = @Translation("GetResponse block"),
 * )
 */
class GetresponseBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('getresponse.settings');
    $script = $config->get('script_url');
    $active = $config->get('webform_on');

    if (empty($script) || !$active) {
      return array();
    }

    return array(
      'rendered' => array(
        '#markup' => '<noscript>' . $this->t("Please enable JavaScript to use the GetResponse service.") .
          '</noscript><script type="text/javascript" src="' . $script . '"></script>',
        '#allowed_tags' => array('noscript', 'script')
      )
    );
  }
}
