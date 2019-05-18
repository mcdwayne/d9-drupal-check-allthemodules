<?php

namespace Drupal\passwd_only\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * A modified login form derived from the user login form of the Drupal Core.
 */
class LoginForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'passwd_only_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('passwd_only.all');
    $passwd_only_uid = $config->get('user');

    // Not configured.
    if (!$passwd_only_uid) {
      $form['warning'] = [
        '#markup' => t(
          'First create or set an user account, you want to use with the password only login module. Go to the @link of the password only login module.',
          [
            '@link' => Link::fromTextAndUrl(
              $this->t('admin page'),
              Url::fromUri('internal:/admin/config/system/passwd-only')
            )->toString(),
          ]
        ),
      ];
      return $form;
    }
    $passwd_only_user = User::load($passwd_only_uid);
    $user = \Drupal::currentUser();
    // Show the login form.
    if (!$user->isAuthenticated()) {
      $form = parent::buildForm($form, $form_state);
      $form['markup'] = [
        '#markup' => $config->get('description'),
      ];
      $form['name'] = [];
      $form['name']['#type'] = 'hidden';
      $form['name']['#value'] = $passwd_only_user->getUsername();
    }
    // Inform that you already logged in.
    else {
      $form['warning'] = [
        '#markup' => t('You are already logged in.'),
      ];
    }
    return $form;
  }

}
