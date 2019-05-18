<?php

namespace Drupal\corporatelogin\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CorporateLoginBlock' block.
 *
 * @Block(
 *  id = "corporate_login_block_form",
 *  admin_label = @Translation("Corporate Login Form"),
 * )
 */
class CorporateLoginBlockForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() { 
    $build = []; 
    $build['corporate_login_block_form'] = \Drupal::formBuilder()->getForm('Drupal\corporatelogin\Form\CorporateLoginForm');
    return $build;
  } 

}
