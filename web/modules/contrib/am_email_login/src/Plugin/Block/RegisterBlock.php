<?php
/**
 * @file
 * Contains \Drupal\am_registration\Plugin\Block\RegisterBlock.
 */
namespace Drupal\am_registration\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
/**
 * Provides a 'registration' block.
 *
 * @Block(
 *   id = "registration_form_block",
 *   admin_label = @Translation("Email Login"),
 *   category = @Translation("Email registration")
 * )
 */
class RegisterBlock extends BlockBase {
  
  // /**
  //  * {@inheritdoc}
  //  */
  // public function access(AccountInterface $account) {
  //   return $account->hasPermission('access content');
  // }  

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\am_registration\Form\RegistrationForm');
    return $form;
    // return array(
    //   '#type' => 'markup',
    //   '#markup' => 'This block list the article.',
    // );
   }
}