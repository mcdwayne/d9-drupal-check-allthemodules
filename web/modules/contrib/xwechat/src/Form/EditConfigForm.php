<?php

/**
 * @file
 * Contains \Drupal\xwechat\Form\EditConfigForm.
 */

namespace Drupal\xwechat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Provides a form for edit a xwechat's config.
 */
class EditConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_config_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xwechat_config = NULL) {
    $form['config']['wid'] = array(
      '#type' => 'hidden',
      '#value' => $xwechat_config->wid,
    );

    $form['config']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Config name'),
      '#description' => t('The config name must be unique.'),
      '#default_value' => $xwechat_config->name,
      '#required' => TRUE,
    );
    $form['config']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('UserName'),
      '#default_value' => $xwechat_config->username,
      '#maxlength' => 128,
    );
    $form['config']['token'] = array(
      '#type' => 'textfield',
      '#title' => t('Token'),
      '#default_value' => $xwechat_config->token,
      '#required' => TRUE,
    );
    $form['config']['appid'] = array(
      '#type' => 'textfield',
      '#title' => t('Appid'),
      '#default_value' => $xwechat_config->appid,
      '#required' => TRUE,
    );
    $form['config']['appsecret'] = array(
      '#type' => 'textfield',
      '#title' => t('Appsecret'),
      '#default_value' => $xwechat_config->appsecret,
      '#required' => TRUE,
    );
    $form['config']['aeskey'] = array(
      '#type' => 'textfield',
      '#title' => t('Aeskey'),
      '#default_value' => $xwechat_config->aeskey,
    );
    $form['config']['bundle'] = array(
      '#type' => 'select',
      '#title' => t('Bundle'),
      '#default_value' => $xwechat_config->bundle,
      '#options' => array('wechat'=>'公众号', 'wechatcorp' => '企业号'),
    );
    $form['config']['agentid'] = array(
      '#type' => 'textfield',
      '#title' => t('AgentID'),
      '#default_value' => $xwechat_config->agentid,
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $configs = xwechat_get_configs(NULL);
    $config_name = array();
    foreach ($configs as $config) {
      $config_name[$config->wid] = $config->name;
    }

    // Remove current config name to prevent false error.
    unset($config_name[$form_state->getValue('wid')]);

    if (!empty($config_name)) {
      // Check if name is unique.
      if (in_array($form_state->getValue('name'), $config_name)) {
        $form_state->setErrorByName('', $this->t('XWechat config %s already exists. Please use a different name.', array('%s' => $form_state->getValue('name'))));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = new Url('xwechat.list');

    $updated = db_update('xwechat_config')
      ->fields(array(
        'name' => $form_state->getValue('name'),
        'username' => $form_state->getValue('username'),
        'token' => $form_state->getValue('token'),
        'appid' => $form_state->getValue('appid'),
        'appsecret' => $form_state->getValue('appsecret'),
        'aeskey' => $form_state->getValue('aeskey'),
        'bundle' => $form_state->getValue('bundle'),
        'agentid' => $form_state->getValue('agentid'),
        'updated' => time(),
      ))
    ->condition('wid', $form_state->getValue('wid'))
    ->execute();
    
    if ($updated) {
      drupal_set_message(t('XWechat config updated.'));
      try {
        $wechatconfig = xwechat_config_load($form_state->getValue('wid'));
        $wechat = new WeChat($wechatconfig);
        $wechat->getAccessToken();
      } catch (Exception $e) {
        drupal_set_message(t('But the configuration did not pass validation'), 'warning');
      }
      
      $form_state->setRedirectUrl($url);
    } else {
      drupal_set_message(t('XWechat config saved failed.'), 'error');
    }
  }

}
