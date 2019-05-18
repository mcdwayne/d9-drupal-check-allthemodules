<?php
/**
 * @file
 * Contains \Drupal\lr_ciam\Plugin\Block\UserSocialLoginBlock.
 */
namespace Drupal\lr_ciam\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'usersociallogin' block.
 *
 * @Block(
 *   id = "user_social_login_block",
 *   admin_label = @Translation("User Login block"),
 *   category = @Translation("Custom User Login block")
 * )
 */

class UserSocialLoginBlock extends BlockBase implements BlockPluginInterface {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'user_login',
      '#items' => array(),
    );
  }
  
    /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['login_block_link_register'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom register interface'),      
      '#default_value' => isset($config['login_block_link_register']) ? $config['login_block_link_register'] : '',
    );
    
    $form['login_block_link_forgot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the link to custom forgot interface'),      
      '#default_value' => isset($config['login_block_link_forgot']) ? $config['login_block_link_forgot'] : '',
    );

    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['login_block_link_register'] = $values['login_block_link_register'];
    $this->configuration['login_block_link_forgot'] = $values['login_block_link_forgot'];
  }  
}