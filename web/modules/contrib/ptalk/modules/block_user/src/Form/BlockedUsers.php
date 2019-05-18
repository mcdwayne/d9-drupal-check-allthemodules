<?php

namespace Drupal\ptalk_block_user\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for blocking and unblocking users.
 */
class BlockedUsers extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Constructs a list of the blocked users.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ptalk_block_user_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['block'] = [
      '#type'   => 'fieldset',
      '#title'  => t('Block an users'),
    ];
    $form['block']['name'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'ptalk.autocomplete',
      // Allows for multiple selections, separated by commas.
      '#required' => TRUE,
      '#weight' => -100,
      '#size' => 60,
      '#title' => t('Block users'),
      '#description' => t('Enter the user, separate users with commas.'),
    ];
    $form['block']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Block users'),
    ];

    $header = array(
      'name' => array(
        'data' => t('Username'),
        'field' => 'u.name',
        'sort'  => 'asc',
      ),
      'operations' => $this->t('Operations'),
    );

    $query = db_select('ptalk_block_user', 'pbu')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->innerJoin('users_field_data', 'u', 'pbu.author = u.uid');
    $query->fields('pbu', ['author'])
      ->condition('pbu.recipient', $this->currentUser->id())
      ->limit(20)
      ->orderByHeader($header);

    $rows = [];
    foreach ($query->execute() as $row) {
      $rows[] = [
        'name' => [
          'data' => [
            '#theme' => 'username',
            '#account' => user_load($row->author),
          ],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'unblock' => [
                'title' => $this->t('Unblock'),
                'url' => Url::fromRoute('ptalk_block_user.unblock_user', ['user' => $row->author]),
              ],
            ],
          ],
        ],
      ];
    }

    $form['blocked'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No blocked users.'),
    );
    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $block = [];
    $invalid = [];
    $names = $form_state->getValue('name');
    foreach (explode(',', $names) as $name) {
      if ($user = user_load_by_name(trim($name))) {
        $block[$user->id()] = $user;
      }
      else {
        array_push($invalid, $name);
      }
    }
    if (!empty($block)) {
      foreach ($block as $id => $account) {
        // Check if the user is already blocked.
        if (ptalk_block_user_author_is_blocked($account, $this->currentUser)) {
          drupal_set_message(t('You have already blocked @account.', ['@account' => $account->getUserName()]), 'warning');
          unset($block[$id]);
          continue;
        }
        // Do not allow users to block themself.
        if ($this->currentUser->id() == $account->id()) {
          drupal_set_message(t('You can not block yourself.'), 'warning');
          unset($block[$id]);
          continue;
        }
      }
    }
    // Display warning about invalid user names.
    if (!empty($invalid)) {
      drupal_set_message(t('The following users do not exist: @invalid.', ['@invalid' => implode(", ", $invalid)]), 'warning');
    }
    // If there are no accounts left, display error.
    if (empty($block)) {
      $form_state->setErrorByName('name', t('You are either not allowed to block these users or the users do not exist.'));
    }
    else {
      $form_state->setValue('block', $block);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->redirectUrl();
  }

  /**
   * Ganerate redirect url.
   */
  public function redirectUrl() {
    return new Url('ptalk_block_user.unblock_user');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $insert = db_insert('ptalk_block_user')->fields(['author', 'recipient']);
    foreach ($form_state->getValue('block') as $account) {
      $insert->values(array(
        'author' => $account->id(),
        'recipient' => $this->currentUser->id(),
      ));
      drupal_set_message(t('@author has been blocked from sending you any further messages.', ['@author' => $account->getUserName()]));
    }
    $insert->execute();
  }

}
