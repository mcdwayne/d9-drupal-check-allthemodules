<?php

namespace Drupal\bulk_invite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UsersInvitedController.
 *
 * @package Drupal\bulk_invite\Controller
 */
class UsersInvitedController extends ControllerBase {

  /**
   * Mail Manager.
   *
   * @var \Drupal\Core\Mail
   */
  protected $mailManager;

  /**
   * Date Formatter.
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * UsersInvitedController constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail Manager Instance.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   */
  public function __construct(MailManagerInterface $mail_manager, DateFormatterInterface $date_formatter) {
    $this->mailManager = $mail_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('date.formatter')
    );
  }

  /**
   * Invited Users List.
   *
   * @return string
   *   Return Hello string.
   */
  public function invitedUsersList() {
    $header = [
      ['data' => $this->t('UID'), 'field' => 'ufd.uid'],
      ['data' => $this->t('Name'), 'field' => 'ufd.name'],
      ['data' => $this->t('Email'), 'field' => 'ufd.mail'],
      ['data' => $this->t('Actions'), 'field' => 'actions'],
    ];

    $query = $this->getDatabase()->select('users_field_data', 'ufd')
      ->fields('ufd', ['uid', 'name', 'mail'])
      ->where('ufd.pass IS NULL')
      ->where( 'ufd.uid != 0')
      ->where( 'ufd.status = 1')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->orderByHeader($header);
    $data = $query->execute();
    $rows = [];
    foreach ($data as $row) {
      $row = (array) $row;

      $link = Link::createFromRoute('Re-Send Invitation', 'bulk_invite.re_send_invitation', ['user' => $row['uid']]);
      $row['actions'] = $link;
      $row['name'] = Link::createFromRoute($row['name'], 'entity.user.canonical', ['user' => $row['uid']]);

      $rows[] = ['data' => $row];

    }
    $build['table_pager'][] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $build['table_pager'][] = [
      '#type' => 'pager',
    ];

    $resend_all = Link::createFromRoute(
      'Re-Send invitation to all the pending users',
      'bulk_invite.re_send_invitation_all',
      [],
      ['attributes' => ['class' => ['button', 'button--primary', 'button--small']]]
    );
    $build['resend_all'] = $resend_all->toRenderable();
    return $build;

  }

  /**
   * Send a email to an specific user.
   *
   * @param \Drupal\user\UserInterface $user
   *   User instance.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function reSendInvitation(UserInterface $user) {
    $to = $user->getEmail();
    $langcode = $user->getPreferredLangcode();

    $result = $this->mailManager->mail('bulk_invite', 'resend_invitation', $to, $langcode, ['account' => $user]);

    if (!$result['result']) {
      drupal_set_message($this->t('There was a problem sending the email, please try again.'), 'error');
    }
    else {
      drupal_set_message($this->t('The message has been sent.'));
    }

    return $this->redirect('bulk_invite.invited_users_controller_list');
  }

  /**
   * Add an email to all the pending users.
   *
   * @todo Use the batch API or create a queue.
   */
  public function reSendInvitationAll() {
    $query = $this->getDatabase()->select('users_field_data', 'ufd')
      ->fields('ufd', ['uid', 'name', 'mail'])
      ->where('ufd.pass IS NULL')
      ->where( 'ufd.uid != 0')
      ->where( 'ufd.status = 1');

    $data = $query->execute();

    foreach ($data as $row) {

      $row = (array) $row;
      $user = User::load($row['uid']);
      $to = $user->getEmail();
      $langcode = $user->getPreferredLangcode();

      $result = $this->mailManager->mail('bulk_invite', 'resend_invitation', $to, $langcode, ['account' => $user]);
      $mail_sent = 0;
      $mail_errors = 0;
      if (!$result['result']) {
        $mail_errors += 0;
      }
      else {
        $mail_sent += 1;
      }

      if ($mail_errors > 0) {
        $message = $this->formatPlural($mail_errors, 'There was a problem sending %count email', 'There was a problem sending %count emails', ['%count' => $mail_errors]);
        drupal_set_message($message, 'error');
      }

      if ($mail_sent > 0) {
        $message = $this->formatPlural($mail_sent, '%count mail was sent', '%count mails were sent.', ['%count' => $mail_sent]);
        drupal_set_message($message);
      }
    }

    return $this->redirect('bulk_invite.invited_users_controller_list');
  }

  /**
   * Get a database instance.
   *
   * Accord with http://drupal.stackexchange.com/a/213657/4362 is not yet
   * possible to inject the database connection.
   *
   * The idea of this method is having a way to mock the db connection on tests.
   *
   * @return \Drupal\Core\Database\Connection
   *   Database connection.
   */
  public function getDatabase() {
    return \Drupal::database();
  }

}
