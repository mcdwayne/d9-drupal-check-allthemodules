<?php

namespace Drupal\comment_admin_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure file system settings for this site.
 */
class CommentAdminNotifyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_admin_notify_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['comment_admin_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['comment'] = array(
      '#type' => 'details',
      '#title' => t('Comment notification'),
      '#open' => TRUE,
    );
    $form['comment']['comment_admin_notify'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => comment_notify_variable_get('comment_admin_notify', TRUE),
    );
    $form['comment']['comment_admin_notify_mailto'] = array(
      '#type' => 'email',
      '#title' => t('E-mail to address'),
      '#default_value' => comment_notify_variable_get('comment_admin_notify_mailto', comment_notify_variable_get_site_email()),
    );
    $form['comment']['comment_admin_notify_subject'] = array(
      '#type' => 'textfield',
      '#title' => t('E-mail subject'),
      '#default_value' => comment_notify_variable_get('comment_admin_notify_subject', 'Comment notification'),
    );
    $form['comment']['comment_admin_notify_mailtext'] = array(
      '#type' => 'textarea',
      '#title' => t('E-mail content'),
      '#default_value' => comment_notify_variable_get('comment_admin_notify_mailtext', comment_admin_default_mailtext()),
      '#return_value' => 1,
      '#cols' => 60,
      '#rows' => 12,
      '#token_types' => array('node', 'comment'),
      '#element_validate' => array('token_element_validate'),
    );
    
    $form['comment']['token_help'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('node', 'comment'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('comment_admin_notify.settings');

    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
