<?php

/**
 * @file
 * Contains \Drupal\flexmail\Form\SubscribeForm.
 */

namespace Drupal\flexmail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexmail\Config\DrupalConfig;
use Drupal\flexmail\FlexmailHelper\FlexmailHelper;
use Finlet\flexmail\FlexmailAPI\FlexmailAPI;

/**
 * Provides a subscription form for Flexmail.
 */
class SubscribeForm extends FormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'flexmail_subscribe_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
    );

    $form['actions'] = array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->t('Subscribe'),
      ),
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = FlexmailHelper::subscribe($form_state->getValue('email'), FlexmailHelper::getListId());

    if ($response instanceof \Exception) {
      drupal_set_message(t('Subscription failed.'), 'error');
    }
    else {
      drupal_set_message(t('You are now subscribed to our mailing list.'));
    }
  }

}

?>