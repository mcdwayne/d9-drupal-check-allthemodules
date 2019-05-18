<?php

namespace Drupal\invite_by_email\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\invite_by_email\Form\InviteByEmailBlockForm;

/**
 * Provides an 'InviteByEmailBlock' block.
 *
 * @Block(
 *  id = "invite_by_email_block",
 *  admin_label = @Translation("Invite By Email Block"),
 *  deriver = "Drupal\invite\Plugin\Derivative\InviteBlock"
 * )
 */
class InviteByEmailBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    $build = [];
    $form = \Drupal::formBuilder()->getForm(new InviteByEmailBlockForm(), $block_id);
    $build['form'] = $form;

    return $build;
  }

}
