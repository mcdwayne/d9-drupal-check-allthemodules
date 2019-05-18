<?php

/**
 * @file
 * Contains \Drupal\google_qr_code\Plugin\Block\google_qr_code_block
 */

namespace Drupal\apply_for_role\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a block for users to apply for roles.
 * @Block(
 *     id = "Apply for roles",
 *     admin_label = @Translation ("Apply For Role"),
 *   )
 */
class apply_for_role_block extends BlockBase{

  // Build out the block.
  public function build(){
    // Get the form and build it out.
    $form = \Drupal::formBuilder()->getForm('Drupal\apply_for_role\Form\ApplyForRoleApplicationForm');

    $render['form'] = $form;
    $render['#cache'] = array(
      'max_age' => 0,
    );

    return $render;
  }

  // Make sure only users who can submit role applications can view the block.
  public function blockAccess(AccountInterface $account){
    return AccessResult::allowedIfHasPermission($account, 'submit role application');
  }
}
