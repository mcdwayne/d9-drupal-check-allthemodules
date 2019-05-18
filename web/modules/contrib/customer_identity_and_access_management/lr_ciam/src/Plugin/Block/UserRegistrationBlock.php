<?php
/**
 * @file
 * Contains \Drupal\lr_ciam\Plugin\Block\UserRegistrationBlock.
 */
namespace Drupal\lr_ciam\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'UserRegistration' block.
 *
 * @Block(
 *   id = "user_registration_block",
 *   admin_label = @Translation("User Register block"),
 *   category = @Translation("Custom User Register block")
 * )
 */

class UserRegistrationBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'user_register',
      '#items' => array(),
    );
  }
  
    /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['register_block_link_login'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom login interface'),      
      '#default_value' => isset($config['register_block_link_login']) ? $config['register_block_link_login'] : '',
    );
    
    $form['register_block_link_forgot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom forgot interface'),      
      '#default_value' => isset($config['register_block_link_forgot']) ? $config['register_block_link_forgot'] : '',
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['register_block_link_login'] = $values['register_block_link_login'];
    $this->configuration['register_block_link_forgot'] = $values['register_block_link_forgot'];
  }  
  
}