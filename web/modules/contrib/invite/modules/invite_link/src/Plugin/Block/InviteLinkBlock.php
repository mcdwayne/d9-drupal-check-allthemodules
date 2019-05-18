<?php

namespace Drupal\invite_link\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\invite_link\Form\InviteLinkBlockForm;

/**
 * Provides an 'InviteLinkBlock' block.
 *
 * @Block(
 *  id = "invite_link_block",
 *  admin_label = @Translation("Invite Link Block"),
 *  deriver = "Drupal\invite\Plugin\Derivative\InviteBlock"
 * )
 */
class InviteLinkBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    $build = [];
    $form = \Drupal::formBuilder()->getForm(new InviteLinkBlockForm(), $block_id);
    $build['form'] = $form;

    return $build;
  }

}
