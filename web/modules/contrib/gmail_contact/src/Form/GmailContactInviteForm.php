<?php
/**
 * @file
 * Contains \Drupal\gmail_contact\Form\GmailContactConfigureForm.
 */

namespace Drupal\gmail_contact\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an example form.
 */
class GmailContactInviteForm extends FormBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new GmailContactInviteForm.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(MailManagerInterface $mail_manager) {
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.mail'));
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'gmailcontact_invite_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Use the contact options from session if this form is not rendered
    // initially. E.g, this form is rendered after validation fails.
    if (isset($_SESSION['gmail_contact_options'])) {
      $options = $_SESSION['gmail_contact_options'];
    }
    else {
      if (!isset($_SESSION['gmail_auth_code'])) {
        $form['text'] = array(
          '#markup' => t('You are not supposed to be on this page.'),
        );
        return $form;
      }

      $auth_code = $_SESSION['gmail_auth_code'];
      $xmlresponse = gmail_contact_get_gmail_contacts($auth_code);
      $contacts = gmail_contact_parse_gmail_contacts($xmlresponse);

      /*$contacts = array(
        array('email' => '22@e.c', 'name' => 'Rulin er'),
        array('email' => '22@e.cs', 'name' => 'Bulin er'),
        array('email' => '22@e.ce', 'name' => 'Aulin aa'),
        array('email' => '22@e.cess', 'name' => 'amlin ac'),
      );*/

      // Display message to user if no contacts found.
      if (empty($contacts)) {
        $message = t("It seems your account doesn't have contacts at this moment.");
        if (variable_get('gmail_contact_name_required', '')) {
          $message .= t("<br>Or all your contacts don't have name associated.
        Please note if one contact doesn't have name, it will not display here.");
        }
        $form['message'] = array(
          '#markup' => $message,
        );

        return $form;
      }

      $options = array();
      $uc = variable_get('gmail_contact_capitalize_name', '');
      foreach ($contacts as $contact) {
        if ($uc) {
          $options[$contact['email']] = ucfirst($contact['name']);
        }
        else {
          $options[$contact['email']] = $contact['name'];
        }
      }

      $sort = variable_get('gmail_contact_sort', '');
      if ($sort) {
        asort($options);
      }

      // Store options in session, so we can use it later, especially when
      // validation fails.
      //
      $_SESSION['gmail_contact_options'] = $options;
    }

    $form['contacts'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $emails = array();
    $all_contacts = $form_state->getValue('contacts');

    foreach ($all_contacts as $contact) {
      if ($contact) {
        $emails[] = $contact;
      }
    }

    if (empty($emails)) {
      $form_state->setErrorByName('gmail_contact_max_result', $this->t('You should select at least one contact to proceed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Figure a better way to get emails only once in validation and
    // submission.
    $emails = array();
    $all_contacts = $form_state->getValue('contacts');

    foreach ($all_contacts as $contact) {
      if ($contact) {
        $emails[] = $contact;
      }
    }

    // Get email from address.
    $from = gmail_contact_get_setting('gmail_contact_email_address');

    // Use site default email.
    if (!$form) {
      $from = \Drupal::config('system.site')->get('mail');
    }

    // Add those email work to drupal queue.
    if (gmail_contact_get_setting('gmail_contact_queue_send')) {
      $queue = \Drupal::queue('gmail_contact_invite');
    }

    foreach ($emails as $email) {
      if (gmail_contact_get_setting('gmail_contact_queue_send')) {
        // Use drupal queue to send emails.
        // Set variables, and add email work to drupal queue.
        $vars = array();
        $vars['email'] = $email;
        $vars['from'] = $from;
        $queue->createItem($vars);
      }
      else {

        // The language of the e-mail. This will one of three values:
        // - $account->getPreferredLangcode(): Used for sending mail to a particular
        //   website user, so that the mail appears in their preferred language.
        // - \Drupal::currentUser()->getPreferredLangcode(): Used when sending a
        //   mail back to the user currently viewing the site. This will send it in
        //   the language they're currently using.
        // - \Drupal::languageManager()->getDefaultLanguage()->getId: Used when
        //   sending mail to a pre-existing, 'neutral' address, such as the system
        //   e-mail address, or when you're unsure of the language preferences of
        //   the intended recipient.
        // In our case, we use site default language.
        $language_code = \Drupal::languageManager()->getDefaultLanguage()->getId();

        // Send the mail, and check for success. Note that this does not guarantee
        // message delivery; only that there were no PHP-related issues encountered
        // while sending.
        $result = $this->mailManager->mail('gmail_contact', 'invite', $email, $language_code, array(), $from);
        if ($result) {
          watchdog('gmail_contact', 'Successfully send e-mail %to).', array('%to' => $email));
        }
      }
    }

    if (gmail_contact_get_setting('gmail_contact_queue_send')) {
      $message = t('Your invitation will be sent shortly.');
    }
    else {
      $message = t('Your invitation has been sent successfully!');
    }
    drupal_set_message($message);

    $form_state->setRedirect('<front>');

    // Remove contact options from session, once one request is done. We should
    // not leave these data in session for long time.
    if (isset($_SESSION['gmail_contact_options'])) {
      unset($_SESSION['gmail_contact_options']);
    }

  }

}

?>
