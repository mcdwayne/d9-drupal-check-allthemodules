<?php

namespace Drupal\facebook_comments_box\Plugin\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Example: uppercase this please' block.
 *
 * @Block(
 *   id = "facebookcomments",
 *   admin_label = @Translation("Facebook comments block")
 * )
 */
class facebookcomments extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	$block = array();
  	$fcb_title = '';
    $node_type = '';

	// Get which node types should have comments.
	$fcb_node_types = \Drupal::config('facebook_comments_box.settings')->get('facebook_comments_box_node_types');
	// Figure out which node and nodetype we're on, puts comments only on
	// nodes (as opposed to the front page, views pages, term pages, etc.)
	if (arg(0) == 'node' && is_numeric(arg(1))) {
	$nid       = arg(1);
	$node      = node_load($nid);
	$node_type = $node->type;
	$fcb_title = check_plain($node->title);
	}

	if (isset($node_type) && in_array($node_type, $fcb_node_types) && $node->status) {

	// Figure out the current absolute URL.
	$path = isset($_GET['q']) ? $_GET['q'] : '<front>';
	$fcb_url = url($path, array('absolute' => TRUE));

	// Meta mark up for the page.
	$markup = '<meta property="og:type" content="Article" />';
	$markup .= '<meta property="og:title" content="' . $fcb_title . '"/>';
	$markup .= '<meta property="og:url" content="' . $fcb_url . '"/>';
	$markup .= '<meta property="og:image" content="' . theme_get_setting('logo') . '"/>';
	$markup .= '<meta property="fb:admins" content="' . check_plain(variable_get('facebook_comments_box_admin_id', NULL)) . '">';

	// Setup the array.
	$metafcb = array(
	'#type' => 'markup',
	'#markup' => $markup,
	);

	// Add meta data to the HEAD.
	drupal_add_html_head($metafcb, 'meta-fcb');

	// Set the title of the block.
	$block['subject'] = t('Facebook Comments Box');

	// Set the content of the block.
	$fcb_content = '<div class="facebook-comments-box">';
	$fcb_content .= '<div id="fb-root"></div>';
	$fcb_content .= '<script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>';
	$fcb_content .= '<fb:comments href="' . $fcb_url . '" ';
	$fcb_content .= ' num_posts="' . check_plain(variable_get('facebook_comments_box_default_comments', 10)) . '" ';
	$fcb_content .= ' width="' . check_plain(variable_get('facebook_comments_box_default_width', 400)) . '" ';
	$fcb_content .= ' colorscheme="' . variable_get('facebook_comments_box_default_theme', 'light') . '" ></fb:comments></div>';

	$block['content'] = $fcb_content;
	}
	return $block;  																																																		
  }

}
