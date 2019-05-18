<?php

namespace Drupal\live_chat_slack\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\live_chat_slack\Slack;

/**
 * Provides a 'Hello' Block
 *
 * @Block(
 *   id = "live_chat_slack_block",
 *   admin_label = @Translation("Live chat slack"),
 * )
 */
class SlackChatBlock extends BlockBase implements BlockPluginInterface{
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $slack_user = $config['live_chat_slack_block_user_name'];
    $slack_user_is_online = FALSE;
    $Slack = new Slack($config['live_chat_slack_block_api_token']);

    $presence = $Slack->call('users.getPresence', array('user' => $config['live_chat_slack_block_user_id']));
    if($presence['ok'] == 1) {
      $slack_user_is_online = TRUE;
    }

    return array(
      '#theme' => 'live_chat_slack_block',
      '#slack_user' => $slack_user,
      '#user_is_online' => $slack_user_is_online,
      '#cache' => array('max-age' => 0),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['live_chat_slack_block_api_token'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#description' => $this->t('Please enter your Slack Team API token here.'),
      '#default_value' => isset($config['live_chat_slack_block_api_token']) ? $config['live_chat_slack_block_api_token'] : '',
    );

    $form['live_chat_slack_block_user_name'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#description' => $this->t('The Slack user that will be used.'),
      '#default_value' => isset($config['live_chat_slack_block_user_name']) ? $config['live_chat_slack_block_user_name'] : '',
    );

    $form['live_chat_slack_block_user_id'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#disabled' => TRUE,
      '#description' => $this->t('The Slack user ID that will be used. This will be filled automatically.'),
      '#default_value' => isset($config['live_chat_slack_block_user_id']) ? $config['live_chat_slack_block_user_id'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('live_chat_slack_block_api_token', $form_state->getValue('live_chat_slack_block_api_token'));
    $this->setConfigurationValue('live_chat_slack_block_user_name', $form_state->getValue('live_chat_slack_block_user_name'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if(!empty($form_state->getValue('live_chat_slack_block_api_token'))) {
      $this->setConfigurationValue('live_chat_slack_block_api_token', $form_state->getValue('live_chat_slack_block_api_token'));
      $Slack = new Slack($form_state->getValue('live_chat_slack_block_api_token'));
      if(!empty($form_state->getValue('live_chat_slack_block_user_name'))) {
        $this->setConfigurationValue('live_chat_slack_block_user_name', $form_state->getValue('live_chat_slack_block_user_name'));
        $slackUserList = $Slack->call('users.list');
        if(!empty($slackUserList)) {
          $found = false;
          foreach($slackUserList['members'] as $key => $slackUser) {
            if($slackUser['name'] == $form_state->getValue('live_chat_slack_block_user_name')) {
              $found = true;
              $this->setConfigurationValue('live_chat_slack_block_user_id', $slackUser['id']);
              $this->setConfigurationValue('live_chat_slack_block_user_image', $slackUser['profile']['image_32']);
            }
          }
          if(!$found) {
            $form_state->setErrorByName('live_chat_slack_block_user_name', 'Slack user with given username not found.');
          }
        }
      } else {
        $form_state->setErrorByName('live_chat_slack_block_user_name', 'Please fill in your Slack user name.');
      }
    } else {
      $form_state->setErrorByName('live_chat_slack_block_api_token', 'Please fill in your Slack API Token.');
    }
  }
}