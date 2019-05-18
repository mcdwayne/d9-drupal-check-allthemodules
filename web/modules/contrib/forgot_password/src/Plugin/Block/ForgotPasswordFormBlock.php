<?php
namespace Drupal\forgot_password\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
####################################################################################################
# Another new concept to Drupal that we need to use for the block is Annotations.                  #
# In order for Drupal to find your block code, you need to implement a code comment                #
# in a specific way, called an Annotation. An Annotation provides basic details of                 #
# the block such as an id and admin label. The admin label will appear on the block listing page.  #
#####################################################################################################
/**
 * Provides a 'Forgot Password' block.
 *
 * @Block(
 *   id = "forgot_password_block",
 *   admin_label = @Translation("Forgot Password"),
 *   category = @Translation("User")
 * )
 */
class ForgotPasswordFormBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\forgot_password\Form\ForgotPasswordForm');
    return $form;
  }
    
}