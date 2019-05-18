<?php

namespace Drupal\bulk_invite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Email\Parse;
use Drupal\user\Entity\User;

/**
 * Class BulkInviteForm.
 */
class BulkInviteForm extends FormBase {

  protected $invalid_emails;
  protected $invitations_sent = [];
  protected $emails;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_invite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $help = <<< EOT
<ul>
  <li>The email template can be <a href="/admin/config/people/accounts#edit-email-admin-created" target="_blank">edited here (new user created by administrator)</a></li>
  <li>The first part of the email is used as the account "username" if you want a different user name you can use the following format: <code>Username &lt;user@email.com&gt;</code></li>
  <li>One email per line.</li>
</ul>
EOT;

    $form['mails'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Paste email addresses below'),
      '#description' => $help,
    );
    $form['actions']['#type'] = 'actions';
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
    $emails = Parse::getInstance()->parse($form_state->getValue('mails'));
    foreach ($emails['email_addresses'] as $email) {
      if ($email['invalid']) {
        $this->invalid_emails[] = "The {$email['original_address']} is not valid because: {$email['invalid_reason']}";
      } else {
        $this->emails[] = $email;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $number_invitations_sent = $this->createUsers($this->emails);

    if (!empty($this->invalid_emails)) {
      foreach ($this->invalid_emails as $invalid) {
        drupal_set_message($invalid, 'error');
      }
    }

    if (!empty($this->invitations_sent)) {
      drupal_set_message('The invitations were sent to the following mails:');
      foreach ($this->invitations_sent as $invitation) {
        drupal_set_message($invitation);
      }
    }
  }

  /**
   * Create new users.
   * @return integer the number of sent invitations.
   */
  protected function createUsers($emails) {
    if (empty($emails)) {
      return 0;
    }
    $number_invitations_sent = 0;

    foreach ($emails as $email) {
      $name = (isset($email['name_parsed']) && !empty($email['name_parsed'])) ? $email['name_parsed'] : $email['local_part_parsed'];
      // Check if the email hasn't been registered yet.
      $query = \Drupal::entityQuery('user', 'OR');
      $query->condition('init', $email['simple_address']);
      $query->condition('mail', $email['simple_address']);
      $query->condition('name', $name);
      $entity_ids = $query->execute();

      if (!empty($entity_ids)) {
        $this->invalid_emails[] = $this->t('The mail: @mail or the user name: @username is already in use.', ['@mail' => $email['simple_address'], '@username' => $name]);
        continue;
      }

      $edit = [];
      $edit['name'] = $name;
      $edit['mail'] = $email['simple_address'];
      $edit['init'] = $email['simple_address'];
      $edit['status'] = 1;

      $account = User::create($edit);
      $account->save();

      $params['account'] = $account;
      $langcode = $account->getPreferredLangcode();
      // Get the custom site notification email to use as the from email address
      // if it has been set.
      $site_mail = \Drupal::config('system.site')->get('mail_notification');
      // If the custom site notification email has not been set, we use the site
      // default for this.
      if (empty($site_mail)) {
        $site_mail = \Drupal::config('system.site')->get('mail');
      }
      if (empty($site_mail)) {
        $site_mail = ini_get('sendmail_from');
      }
      \Drupal::service('plugin.manager.mail')->mail('user', 'register_admin_created', $account->getEmail(), $langcode, $params, $site_mail);
      $number_invitations_sent += 1;
      $this->invitations_sent[] = $this->t("@email (username: @username) ", ['@username' => $edit['name'], '@email' => $edit['mail']]);
    }
    return $number_invitations_sent;
  }

}
