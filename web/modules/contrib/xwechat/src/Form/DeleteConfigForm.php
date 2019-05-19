<?php

/**
 * @file
 * Contains \Drupal\xwechat\Form\DeleteConfigForm.
 */

namespace Drupal\xwechat\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

/**
 * Provides a deletion confirmation form for xwechat config.
 */
class DeleteConfigForm extends ConfirmFormBase {

  /**
   * The xwechat config.
   *
   * @var string
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xwechat_del_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the xwechat config %name?', array('%name' => $this->config->name));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('xwechat.list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xwechat_config = NULL) {
    $this->config = $xwechat_config;
    if ($xwechat_config) {
      $form['wid'] = array(
        '#type' => 'value',
        '#value' => $xwechat_config->wid,
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (xwechat_config_confirm_delete($form_state->getValue('wid'))) {
      $this->logger('xwechat_config')->notice('Deleted %name', array('%name' => $this->config->name));
      drupal_set_message($this->t('The xwechat config %name was deleted.', array('%name' => $this->config->name)));
    }
    else {
      drupal_set_message(t('There was a problem deleting the xwechat config'), 'error');
    }
    
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
