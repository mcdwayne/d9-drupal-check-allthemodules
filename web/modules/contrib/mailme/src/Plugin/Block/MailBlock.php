<?php
/**
 * @file
 * Contains \Drupal\mailme\Plugin\Block\MailBlock.
 */
namespace Drupal\mailme\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'Mail' Block by displaying a custom form.
 *
 * @Block(
 *   id = "Mail_block",
 *   admin_label = @Translation("MailMe Block"),
 *   category = @Translation("Custom!"),
 * )
 */
class MailBlock extends BlockBase {
	/**
	*	{@inheritdoc}
	*/
	public function build() {
		
		$build = array();		
		$form = \Drupal::formBuilder()->getForm('Drupal\mailme\Form\MailForm');
		return $form;		
	}
}