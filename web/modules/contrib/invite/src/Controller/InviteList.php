<?php

namespace Drupal\invite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\invite\InviteConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Active user list controller.
 */
class InviteList extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * An array that contains invite status and related user readable name.
   *
   * @var array
   */
  public $inviteStatus;

  /**
   * Constructs a new InviteList object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Connection database.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    $this->inviteStatus = [
      InviteConstants::INVITE_VALID => $this->t('Active'),
      InviteConstants::INVITE_WITHDRAWN => $this->t('Withdrawn'),
      InviteConstants::INVITE_USED => $this->t('Used'),
      InviteConstants::INVITE_EXPIRED => $this->t('Expired'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Returns the active user list output.
   *
   * @return array
   *   A renderable array.
   */
  public function view() {

    $header = [
      ['data' => $this->t('Status')],
      ['data' => $this->t('Sender')],
      ['data' => $this->t('E-mail')],
      ['data' => $this->t('Operations')],
    ];

    $query = $this->database->select('invite', 'i');
    $query->fields('ufd', ['mail']);
    $query->fields('i', ['id', 'status']);
    $query->fields('ie', ['field_invite_email_address_value']);
    $query->leftJoin('users', 'u', 'i.user_id = u.uid');
    $query->leftJoin('users_field_data', 'ufd', 'u.uid = ufd.uid');
    $query->leftJoin('invite__field_invite_email_address', 'ie', 'i.id = ie.entity_id');
    $query->orderBy('i.id', 'desc');

    $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->limit(20);
    $result = $query->execute();

    $rows = [];
    foreach ($result as $row) {
      $operations = [
        '#type' => 'operations',
        '#links' => $this->getOperations($row->id),
        '#attributes' => [],
      ];
      $rows[] = [
        'data' => [
          'status' => $this->inviteStatus[$row->status],
          'mail' => $row->mail,
          'field_invite_email_address_value' => $row->field_invite_email_address_value,
          'operations' => render($operations),
        ],
      ];
    }

    $output['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $output['pager'] = [
      '#type' => 'pager',
    ];

    return $output;
  }

  /**
   * Get operations links like withdraw and resend invitation.
   *
   * @param int $invitation_id
   *   Invite entity id (invite id).
   *
   * @return array
   *   Array of withdraw and resend links.
   */
  public function getOperations($invitation_id) {
    $links[] = [
      'title' => $this->t('Withdraw'),
      'url' => Url::fromRoute('invite.invite_withdraw_form',
        ['invite' => $invitation_id]
      ),
    ];
    $links[] = [
      'title' => $this->t('Resend'),
      'url' => Url::fromRoute('invite.invite_resend_form',
        ['invite' => $invitation_id]
      ),
    ];
    return $links;
  }

}
