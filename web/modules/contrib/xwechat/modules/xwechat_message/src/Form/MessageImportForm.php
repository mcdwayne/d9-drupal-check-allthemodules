<?php

/**
 * @file
 * Contains \Drupal\xwechat_message\Form\MessageImportForm.
 */

namespace Drupal\xwechat_message\Form;

use Drupal\Core\Form\ConfigFormBase;
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
class MessageImportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_import_message';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xwechat.message.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wid     = $request->wechat->getConfig('wid', '0');
    $openid  = $request->getParameter('FromUserName');
    $msgtype = $request->getParameter('MsgType');
    $subtype = WeChat::getHandleEvent($request);
    if ($subtype == 'reportlocation') {
        return db_update('xwechat_user')
                    ->fields(array(
                        'lat'  => $request->getParameter('Latitude', 0),
                        'lng'  => $request->getParameter('Longitude', 0),
                    ))
                    ->condition('openid', $openid)
                    ->execute();
    }
    db_insert('xwechat_message')
      ->fields(array(
          'wid'       => $wid,
          'openid'    => $openid,
          'timestamp' => REQUEST_TIME,
          'type'      => 'receive',
          'msgtype'   => strtolower($msgtype),
          'subtype'   => $subtype,
          'data'      => json_encode($request->getParameters()),
      ))
      ->execute();    

    return parent::buildForm($form, $form_state);
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

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if(!empty($form_state->getValue())){
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

    parent::submitForm($form, $form_state);
  }

}

