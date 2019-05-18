<?php

/**
 * @file
 * Contains \Drupal\gplus_comments_block\Plugin\Block\GplusComments.
 */

namespace Drupal\gplus_comments_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a Drupal Comments Box block.
 *
 * @Block(
 *   id = "gplus_comments_block",
 *   admin_label = @Translation("Google Comments Block"),
 * )
 */
class GplusComments extends BlockBase {

	/**
	 * {@inheritdoc}
	 */
	public function build() {
    $path_args = explode('/', current_path());

    if ($path_args[0] == 'node' && is_numeric($path_args[1])) {
      $div = \Drupal\Component\Utility\Html::getUniqueId('gplus_comments');
      $current_url = Url::fromRoute(
        'entity.node.canonical',
        array(
          'node' => $path_args[1],
          ),
        array(
          'absolute' => TRUE
        )
      )->toString();
      $content = gplus_comments_block_render_content_block($div, $current_url);
    }
    else {
      $content = '';
    }

		return array(
			'#children' => render($content),
		);
	}
}
