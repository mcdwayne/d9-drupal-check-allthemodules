<?php

/**
 * @file
 * Contains \Drupal\xwechat\Form\AddConfigForm.
 */

namespace Drupal\xwechat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Configure xwechat settings for this site.
 */
class AddConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_add_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    static $bundles = array('wechat' => '公众号', 'wechatcorp' => '企业号');
    // get xwechat config.
    $rows = array();
    $configs = xwechat_get_configs(NULL);

    foreach ($configs as $config) {
      $links = \Drupal::moduleHandler()->invokeAll('xwechat_operation', $args = array(array(), (array)$config));

      $row = [];
      $row['wid']['data'] = $config->wid;
      $row['qrcode']['data'] = array(
        '#type' => 'inline_template',
        '#template' => '<img class="wechat-qrcode" src="http://open.weixin.qq.com/qr/code/?username={{ username }}" />',
        '#context' => array(
          'username' => $config->username,
        ),  
      );
      $row['name']['data'] = Html::escape($config->name);
      $row['config']['data'] = array(
        '#type' => 'inline_template',
        '#template' => 'AppID: <strong> {{ appid }} </strong> <br>
                        Token: {{ token }} <br>
                        AppSecret: {{ appsecret }} <br>
                        EncodingAESKey: {{ aeskey }} <br>
                        AgentID: {{ agentid }}',
        '#context' => array(
          'appid' => $config->appid,
          'token' => xwechat_config_mask($config->token),
          'appsecret' => xwechat_config_mask($config->appsecret),
          'aeskey' => xwechat_config_mask($config->aeskey),
          'agentid' => $config->agentid,
        ),  
      );
      $row['bundle']['data'] = isset($bundles[$config->bundle]) ? $bundles[$config->bundle] : $config->bundle;
      $row['updated']['data'] = $config->updated ? date('Y-m-d H:i:s', $config->updated) : date('Y-m-d H:i:s', $config->created);
      
      $row['operate']['data'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
      $rows[] = $row;
    }

    if (empty($rows)) {
      $rows[] = array(array(
          'data' => t('No xwechat config available.'),
          'colspan' => '7',
        ));
    }

    $header = array(t('WID'), t('Qrcode'), t('Name'), t('Config'), t('Bundle'), t('Updated'), t('Operation'));

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'xwechat-configs',
      ),
    );
    $markup = drupal_render($table);

    $form = array();
    $form['list'] = array(
      '#type' => 'fieldset',
      '#title' => t('xwechat configs'),
      '#collapsible' => TRUE,
    );
    $form['list']['table'] = array(
      '#type' => 'item',
      '#prefix' => '<div>',
      '#markup' => $markup,
      '#suffix' => '</div>',
    );

    // add xwechat config.
    $form['config'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add xwechat config'),
    );
    $form['config']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Config name'),
      '#description' => t('A config with this same name will be created.'),
      '#default_value' => '',
      '#required' => TRUE,
    );
    $form['config']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('UserName'),
      '#default_value' => '',
      '#maxlength' => 128,
    );
    $form['config']['token'] = array(
      '#type' => 'textfield',
      '#title' => t('Token'),
      '#default_value' => '',
      '#required' => TRUE,
    );
    $form['config']['appid'] = array(
      '#type' => 'textfield',
      '#title' => t('Appid'),
      '#default_value' => '',
      '#required' => TRUE,
    );
    $form['config']['appsecret'] = array(
      '#type' => 'textfield',
      '#title' => t('Appsecret'),
      '#default_value' => '',
      '#required' => TRUE,
    );
    $form['config']['aeskey'] = array(
      '#type' => 'textfield',
      '#title' => t('Aeskey'),
      '#default_value' => '',
    );
    $form['config']['bundle'] = array(
      '#type' => 'select',
      '#title' => t('Bundle'),
      '#default_value' => 'wechat',
      '#options' => array('wechat'=>'公众号', 'wechatcorp' => '企业号'),
    );
    $form['config']['agentid'] = array(
      '#type' => 'textfield',
      '#title' => t('AgentID'),
      '#default_value' => '0',
    );

    $form['#attached']['library'][] = 'xwechat/xwechat.main';

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
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
      $config_name[] = $config->name;
    }

    if (!empty($config_name)) {
      // Check if name is unique
      if (in_array($form_state->getValue('name'), $config_name)) {
        $form_state->setErrorByName('', $this->t('Xwechat config %s already exists. Please use a different name.', array('%s' => $form_state->getValue('name'))));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if(!empty($form_state->getValues())){
      $wid = db_insert('xwechat_config')
      ->fields(array(
        'name' => $form_state->getValue('name'),
        'username' => $form_state->getValue('username'),
        'token' => $form_state->getValue('token'),
        'appid' => $form_state->getValue('appid'),
        'appsecret' => $form_state->getValue('appsecret'),
        'aeskey' => $form_state->getValue('aeskey'),
        'bundle' => $form_state->getValue('bundle'),
        'agentid' => $form_state->getValue('agentid'),
        'created' => time(),
      ))
      ->execute();
    }

    if ($wid) {
      drupal_set_message(t('XWechat config %s added.', array('%s' => $form_state->getValue('name'))));
      try {
        $wechatconfig = xwechat_config_load($wid);
        $wechat = new WeChat($wechatconfig);
        $wechat->getAccessToken();
      } catch (Exception $e) {
        drupal_set_message(t('But the configuration did not pass validation'), 'warning');
      }
    } else {
      drupal_set_message(t('XWechat config saved failed.'), 'error');
    }
  }

}

