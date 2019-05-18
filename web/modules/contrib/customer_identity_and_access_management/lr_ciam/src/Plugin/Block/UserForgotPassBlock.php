<?php
/**
 * @file
 * Contains \Drupal\lr_ciam\Plugin\Block\UserForgotPassBlock.
 */
namespace Drupal\lr_ciam\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Forgot Password' block.
 *
 * @Block(
 *   id = "user_forgot_pass_block",
 *   admin_label = @Translation("User Forgot Password block"),
 *   category = @Translation("Custom User Forgot Password block")
 * )
 */

class UserForgotPassBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'user_pass',
      '#items' => array(),
    );
  }
  
   
    /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['forgot_block_link_login'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom login interface'),      
      '#default_value' => isset($config['forgot_block_link_login']) ? $config['forgot_block_link_login'] : '',
    );
    
    $form['forgot_block_link_register'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom register interface'),      
      '#default_value' => isset($config['forgot_block_link_register']) ? $config['forgot_block_link_register'] : '',
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['forgot_block_link_login'] = $values['forgot_block_link_login'];
    $this->configuration['forgot_block_link_register'] = $values['forgot_block_link_register'];
  }  
  
}