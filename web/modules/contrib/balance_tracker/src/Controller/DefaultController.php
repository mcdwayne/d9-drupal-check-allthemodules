<?php

namespace Drupal\balance_tracker\Controller;

use Drupal\balance_tracker\Element\BalanceTable;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Default controller for the balance_tracker module.
 */
class DefaultController extends ControllerBase {

  /**
   * Route controller for admin view of all user balances.
   */
  public function allBalancesPage() {
    $output = [
      '#cache' => [
        'tags' => ['balance_tracker'],
      ],
      'header1' => [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('Here you can view the balances for all users.'),
      ],
      'header2' => [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('The balance column is sortable. Click on a user\'s name to get their balance sheet.'),
      ],
    ];

    $header = array(
      'uid' => array('data' => $this->t('User'), 'sort' => 'uid'),
      'balance' => array(
        'data' => $this->t('Balance'),
        'sort' => 'desc',
      ),
    );

    $rows = [];
    foreach (\Drupal::service('balance_tracker.storage')->getAllUserBalances() as $result) {
      // Swap the UID result for a fully formatted link to the user's balance.
      /** @var \Drupal\user\Entity\User $user */
      if ($user = User::load($result->uid)) {
        $row['user'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $user->getDisplayname(),
            '#url' => new Url('balance_tracker.user_balance', ['user' => $user->id()]),
          ],
        ];
        $row['balance'] = BalanceTable::formatCurrency($result->balance);
        $rows[] = $row;
      }
    }

    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('You don\'t appear to have any users with balances.'),
    ];
    $output['pager'] = [
      '#type' => 'pager',
      '#tags' => [],
    ];
    return $output;
  }

  /**
   * Title callback for the balance_tracker.user_balance route.
   */
  public function getPageTitle(UserInterface $user) {
    return $this->t('@user\'s balance', ['@user' => $user->getDisplayName()]);
  }

  /**
   * Redirects to a user's own balance page.
   */
  public function userPage() {
    return $this->redirect('balance_tracker.user_balance', ['user' => $this->currentUser()->id()]);
  }

  /**
   * Access check callback for user balance form routes.
   */
  public function balanceFormAccess(UserInterface $user) {
    $access = AccessResult::allowedIfHasPermission($this->currentUser(), 'view all balances');
    return $access->orIf(AccessResult::allowedIf($this->currentUser()->hasPermission('view own balance') && $user->id() === $this->currentUser()->id()));
  }

}
