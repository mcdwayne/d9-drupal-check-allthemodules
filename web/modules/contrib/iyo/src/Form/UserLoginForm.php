<?php

namespace Drupal\itsyouonline\Form;

use Drupal\user\Form\UserLoginForm as CoreUserLoginForm;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure itsyouonline account for this site.
 */
class UserLoginForm extends CoreUserLoginForm {


  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'itsyouonline_user_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = array('max-age' => 0);

    $form['itsyouonline_info'] = array(
      '#type' => 'markup',
      '#markup' => t('Complete this form if you already have an account on this website. Enter your credentials to link this account to itsyou.online. As soon as your account is linked, you will be able to log in to this site with your itsyou.online authenticator.')
    );

    return parent::buildForm($form, $form_state);
  }

}
