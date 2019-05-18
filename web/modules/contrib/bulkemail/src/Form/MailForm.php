<?php

/**
 * @file
 * Contains \Drupal\bulkemail\Form\MailForm.
 */

namespace Drupal\bulkemail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Contribute form.
 */
class MailForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'bulk_mail';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $current_user = \Drupal::currentUser();
        $user = \Drupal\user\Entity\User::load($current_user->id());
        $name = $user->getUsername();
        $u_mail = $user->getEmail();

        $form['email_address'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Recipients'),
            '#description' => t("You can copy/paste the multiple emails, enter one email per line. "),
            '#required' => TRUE
        );
        $form['subject'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Subject'),
            '#required' => TRUE
        );
        $form['message'] = array(
            '#type' => 'text_format',
            '#title' => $this->t('Message'),
            '#required' => TRUE
        );
        $form['email_address_from'] = array(
            '#type' => 'email',
            '#title' => $this->t('From e-mail address'),
            '#required' => TRUE,
            '#default_value' => isset($u_mail) ? $u_mail : ''
        );
        $form['user'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Your name'),
            '#required' => TRUE,
            '#default_value' => isset($name) ? $name : ''
        );
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Send'),
            '#button_type' => 'primary',
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $message_val = $form_state->getValue('message');
        $message = $message_val['value'];
        if (!filter_var($form_state->getValue('email_address_from', FILTER_VALIDATE_EMAIL))) {
            $form_state->setErrorByName('email_address', $this->t('The Email Address you have provided is invalid.'));
        }//end if.
        if (empty(($form_state->getValue('email_address')))) {
            $form_state->setErrorByName('email_address', $this->t('Recipients field is required.'));
        }//end if.
        if (empty(($form_state->getValue('subject')))) {
            $form_state->setErrorByName('email_address', $this->t('Subject field is required.'));
        }//end if.
        if (empty($message)) {
            $form_state->setErrorByName('email_address', $this->t('Message field is required.'));
        }//end if.
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $email_val = $form_state->getValue('email_address');
        $recipient_email = explode("\r\n", $email_val);
        $sub_val = $form_state->getValue('subject');
        $message_val = $form_state->getValue('message');
        $message = $message_val['value'];
        $email_from = $form_state->getValue('email_address_from');

        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'bulk_mail';
        $key = 'bulk_mail_send'; // Replace with Your key
        $params['message'] = $message;
        $params['title'] = $sub_val;
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;
        foreach ($recipient_email AS $to) {
            $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
        } //end foreach.
        //Check result sucess or not.
        if ($result['result'] != true) {
            $message = t('There was a problem sending your email notification');
            drupal_set_message($message, 'error');
            \Drupal::logger('mail-log')->error($message);
            return;
        }//end if.


        drupal_set_message(t('An email has been sent'), 'status');
        
    }

}
