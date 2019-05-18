<?php

namespace Drupal\passwd_only\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * The configuration form of the module.
 */
class ConfigurationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'passwd_only_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('passwd_only.all');

    $uid = $config->get('user');

    if ($uid) {
      $user = User::load($uid);
    }
    else {
      $user = NULL;
    }

    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select a password only login user'),
      '#description' => $this->t('Select a user to login in the password only login forms.'),
      '#size' => 60,
      '#maxlength' => 60,
      '#target_type' => 'user',
      '#default_value' => $user,
    ];

    if ($uid) {
      $form['change_password'] = [
        '#type' => 'item',
        '#title' => $this->t('Change password'),
        '#markup' => Link::fromTextAndUrl(
          $this->t('Go to the password only login user.'),
          Url::fromUri('internal:/user/' . $uid . '/edit')
        )->toString(),
      ];
    }

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This description text is shown on the password only login form.'),
      '#default_value' => $config->get('description'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('user');
    if ($uid == 1) {
      $user = User::load($uid);
      $form_state->setErrorByName(
        'user',
        $this->t(
          '"@name" is the root user account (User-ID 1). It is not secure to use this account with Password Only Login. Please select another user account.',
          ['@name' => $user->getDisplayName()]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('passwd_only.all');
    $config->set('description', $form_state->getValue('description'))->save();
    $config->set('user', $form_state->getValue('user'))->save();
  }

}
