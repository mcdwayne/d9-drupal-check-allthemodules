<?php

namespace Drupal\chatroll\Plugin\Block;

use Symfony\Component\Validator\Constraints\True;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ChatrollBlock' block.
 *
 * @Block(
 *  id = "chatroll_block",
 *  admin_label = @Translation("Chatroll block"),
 * )
 */
class ChatrollBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'chatroll_block_shortcode' => '',
      'chatroll_block_width' => '100%',
      'chatroll_block_height' => '100%',
      'chatroll_block_showlink' => '1',
    ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['chatroll_block_shortcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chatroll Shortcode'),
      '#description' => $this->t('Copy and paste your Chatroll Shortcode here.<br/>Your Shortcode can be found on your Chatroll\'s <b>Embed Code</b> -> <b>Drupal</b> page on <a href="http://chatroll.com">chatroll.com</a>'),
      '#default_value' => $this->configuration['chatroll_block_shortcode'],
      '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
    ];

    $form['chatroll_block_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['chatroll_block_width'],
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => $this->t('Set the width of your Chatroll'),
      '#required' => TRUE,
    ];
    $form['chatroll_block_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['chatroll_block_height'],
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => $this->t('Set the height of your Chatroll. <br/><br/><b>Additional settings</b> and <b>moderation tools</b> can be found on your Chatroll\'s <b>Settings</b> page on <a href="http://chatroll.com">chatroll.com</a>):<ul style="font-weight:bold;"><li>Colors</li><li>Sound</li><li>Single Sign On (SSO)</li><li>White Label</li></ul>'),
      '#required' => TRUE,
    ];
    $form['chatroll_block_showlink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show link below widget'),
      '#default_value' => $this->configuration['chatroll_block_showlink'],
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['chatroll_block_shortcode'] = $form_state->getValue('chatroll_block_shortcode');
    $this->configuration['chatroll_block_width'] = $form_state->getValue('chatroll_block_width');
    $this->configuration['chatroll_block_height'] = $form_state->getValue('chatroll_block_height');
    $this->configuration['chatroll_block_showlink'] = $form_state->getValue('chatroll_block_showlink');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['chatroll_rendered_block']['#markup'] = '';
    $chatroll = new DrupalChatroll();
    $chatroll_block_configuration = $this->getConfiguration();
    $chatroll->width = isset($chatroll_block_configuration['chatroll_block_width']) ? $chatroll_block_configuration['chatroll_block_width'] : '450';
    $chatroll->height = isset($chatroll_block_configuration['chatroll_block_height']) ? $chatroll_block_configuration['chatroll_block_height'] : '350';
    $chatroll->showlink = isset($chatroll_block_configuration['chatroll_block_showlink']) ? $chatroll_block_configuration['chatroll_block_showlink'] : '1';
    if (isset($chatroll_block_configuration['chatroll_block_shortcode']) && !empty($chatroll_block_configuration['chatroll_block_shortcode'])) {
      $build['chatroll_rendered_block']['#markup'] = \Drupal\Core\Render\Markup::create($chatroll->renderChatrollHtmlFromShortcode($chatroll_block_configuration['chatroll_block_shortcode']));
    }
    return $build;
  }
}

