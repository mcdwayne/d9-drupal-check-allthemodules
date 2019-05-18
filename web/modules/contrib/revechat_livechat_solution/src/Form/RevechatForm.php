<?php

namespace Drupal\revechat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Contains \Drupal\revechat\Form\RevechatForm.
 */
class RevechatForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revechat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('revechat.settings');
    $aid = $config->get('revechat.revechat_aid');

    if ($this->isInstalled($aid)) {
      $form['revechat_success_message'] = array(
        '#markup' => '<div class="box">
                              <h3>' . t('REVE Chat has been installed.') . '</h3>'.
                '<p>'.t('Sign in to REVE Chat Dashboard and start chatting with your customers.').'</p>'.
                '<p><a href="https://dashboard.revechat.com" class="dashboard-btn" target="_blank">Go to Dashboard</a></p>'
                .'</div>',
      );

      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#prefix' => '<div class="remove_btn"><p><small>Something went wrong?</small>',
        '#value' => t('Disconnect'),
        '#suffix' => '</p></div>',
      );
    }
    else {
      $form['choose_form'] = array(
        '#type'            => 'radios',
        '#title'         => $this->t('Do you already have a REVE Chat account?'),
        '#default_value' => 'has_revechat_account',
        '#options'        => array(
          'has_revechat_account' => $this->t('Yes, I already have a REVE Chat account'),
          'new_revechat_account' => $this->t('No, I want to create one'),
        ),
      );

      $form['revechat_already_have'] = array(
        '#type' => 'fieldset',
        '#title' => '',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#prefix' => '<div id="edit-revechat-already-have"><h3>Login into REVE Chat Account</h3>',
        '#suffix' => '</div>',
      );
      $form['revechat_already_have']['ajax_message'] = array(
        '#type' => 'item',
        '#markup' => '<p class="ajax_message"></p>',
      );
      $form['revechat_already_have']['revechat_account_email'] = array(
        '#type' => 'email',
        '#title' => $this->t('REVE Chat Login Email'),
      );

      $form['revechat_already_have']['revechat_aid'] = array(
        '#type' => 'hidden',
        '#default_value' => $config->get('revechat.revechat_aid'),
      );

      // New Account Form.
      $form['new_revechat_account'] = array(
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#prefix' => '<div id="revechat_new_account"><h3>Create a new REVE Chat account</h3>',
        '#suffix' => '</div>',
      );
      $form['new_revechat_account']['ajax_message'] = array(
        '#type' => 'item',
        '#markup' => '<p class="ajax_message"></p>',
      );
      $form['new_revechat_account']['firstName'] = array(
        '#type' => 'textfield',
        '#title' => t('First Name'),
        '#required' => FALSE,
      );

      $form['new_revechat_account']['lastName'] = array(
        '#type' => 'textfield',
        '#title' => t('Last Name'),
        '#required' => FALSE,
      );

      $form['new_revechat_account']['email'] = array(
        '#type' => 'textfield',
        '#title' => t('Email'),
        '#required' => FALSE,
      );

      $form['new_revechat_account']['accountPassword'] = array(
        '#type' => 'password',
        '#title' => t('Create Password'),
        '#required' => FALSE,
      );

      $form['new_revechat_account']['retypePassword'] = array(
        '#type' => 'password',
        '#title' => t('Confirm your Password'),
        '#required' => FALSE,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('op') === t('Disconnect')) {
      $config = $this->config('revechat.settings');
      $config->delete('revechat.revechat_aid');
      $config->save();
    }
    else {
      $config = $this->config('revechat.settings');
      $config->set('revechat.revechat_aid', $form_state->getValue('revechat_aid'));
      $config->save();
    }
    drupal_flush_all_caches();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Get editable Configuration names.
   */
  protected function getEditableConfigNames() {
    return ['revechat.settings'];
  }

  /**
   * Check if REVE Chat aid is exists.
   */
  private function isInstalled($aid) {
    if (!empty($aid) || $aid != 0) {
      return TRUE;
    }
    return FALSE;
  }

}
