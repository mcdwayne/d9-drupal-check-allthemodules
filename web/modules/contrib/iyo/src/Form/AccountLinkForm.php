<?php

namespace Drupal\itsyouonline\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure itsyouonline account for this site.
 */
class AccountLinkForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'itsyouonline_link_account';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('itsyouonline.account');

    if ($config->get('auto_create_account') == 1) {
      return new RedirectResponse(\Drupal::url('itsyouonline.link_new_user'));
    }

    $form['item'] = array(
      '#type' => 'item',
      '#markup' => t("Your itsyou.online account is not yet linked to a user account on this website. If you already have a user account on this website, you can link it to your itsyou.online account. If you don't have a user account yet, create one and it will be linked to your itsyou.online account."),
    );

    $scenarios = array(
      'new_user' => t("I don't have a user account yet on this website. I want to create one and link it to my itsyou.online account."),
      'existing_user' => t("I already have a user account on this website. I want to link it to my itsyou.online account."),
    );

    $form['scenario'] = array(
      '#type' => 'radios',
      '#title' => t('Select one of the following options'),
      '#required' => TRUE,
      '#options' => $scenarios,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Continue'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('scenario') == 'new_user') {
      $form_state->setRedirect('itsyouonline.link_new_user');
    }
    else {
      $form_state->setRedirect('itsyouonline.link_existing_user');
    }
  }

}