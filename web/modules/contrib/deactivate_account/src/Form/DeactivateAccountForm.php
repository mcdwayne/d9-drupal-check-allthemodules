<?php

namespace Drupal\deactivate_account\Form;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\user;

use Drupal\Core\Render\Element;

/**
 * Implements a DeactivateAccount form.
 */
class DeactivateAccountForm extends FormBase {

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deactivate_account_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $user = '', $deactivate = '') {
    $account = $this->account;
    $config = $this->config('deactivate_account.settings');

    $form = array();
    $time = array();
    $deactivate_account_time_options = $config->get('deactivate_account_time_option');

    for ($i = 0; $i <= \Drupal::state()->get('deactivate_account_total_options'); $i++) {
      if ($deactivate_account_time_options['name']['deactivate_account_time_' . $i] == 1) {
        $time[$deactivate_account_time_options['name']['deactivate_account_time_' . $i]] = $deactivate_account_time_options['name']['deactivate_account_time_' . $i] . ' hour';
      }
      else {
        $time[$deactivate_account_time_options['name']['deactivate_account_time_' . $i]] = $deactivate_account_time_options['name']['deactivate_account_time_' . $i] . ' hours';
      }
    }

    $form['delete'] = array(
      '#type' => 'details',
      '#title' => $this->t('Delete Account'),
      '#description' => $this->t("Are you sure you want to PERMANENTLY DELETE your account?"),
      '#open' => TRUE,
    );

    $form['delete']['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email or username'),
      '#required' => TRUE,
      '#default_value' => $account->getUsername(),
    );

    $form['delete']['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    );

    $form['delete']['delete_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('DELETE MY ACCOUNT'),
      '#validate' => array('deactivate_account_form_validate'),
      '#submit' => array('::deactivate_account_delete'),

    );

    // And put the deactivate part in a separate frameset.
    if ($deactivate_account_time_options['name']['deactivate_account_time_0'] != "") {
      $form['disable'] = array(
        '#type' => 'details',
        '#title' => $this->t('Temporary deactivate account'),
      );

      $form['disable']['options'] = array(
        '#type' => 'radios',
        '#title' => $this->t('How long you want to deactive your account?'),
        '#options' => $time,
      );

      $form['disable']['disable_submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('OKAY'),
        '#validate' => array('deactivate_account_form_validate'),
        '#submit' => array('::deactivate_account_deactivate'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Deleting user account on request.
   */
  public function deactivate_account_delete(array &$form, FormStateInterface $form_state) {
    $config = $this->config('deactivate_account.settings');
    $account = $this->account;
    // Delete the account.
    user_delete($account->id());
    // Set a message and send the user to the homepage.
    drupal_set_message($this->t('Your account has been deleted'));

    if($config->get('deactivate_account_redirect')) {
      $form_state->setRedirect($config->get('deactivate_account_redirect'));
    }
    else {
      $url = Url::fromUserInput($config->get())->setAbsolute()->toString();
      return new RedirectResponse($url);
    }
  }

  /**
   * Deactivating user.
   */
  public function deactivate_account_deactivate(&$form, FormStateInterface $form_state) {
    $time = time();
    $expiration = $time + $form_state->getValues()['options'] * 60 * 60;
    $this->deactivate_account_deactivate_user($this->account, $expiration);
  }

  /**
   * Deactivates the account and save it.
   */
  public function deactivate_account_deactivate_user($account, $expiration_time) {
    $config = $this->config('deactivate_account.settings');
    $account_uid = $account->id();
    $account_name = $account->getUsername();

    $account_exist = \Drupal::database()->select('deactivate_account', 'n')
      ->fields('n', array('uid'))
      ->condition('uid', $account_uid)
      ->execute()->fetchField();

    if ($account_exist) {
      \Drupal::database()->update('deactivate_account')
        ->fields(array(
          'expiration' => $expiration_time,
        ))
        ->condition('uid', $account_uid)
        ->execute();

      $account = User::load($account_uid);
      $account->block();
      $account->save();

      self::deactivate_account_disable_nodes($account);
      self::deactivate_account_disable_comments($account);
      return new RedirectResponse($config->get('deactivate_account_redirect'));
    }
    else {
      \Drupal::database()->insert('deactivate_account')
        ->fields(array(
          'uid' => $account_uid,
          'name' => $account_name,
          'expiration' => $expiration_time,
        ))
        ->execute();

      $account = User::load($account_uid);
      $account->block();
      $account->save();
      $this->deactivate_account_disable_nodes($account);
      $this->deactivate_account_disable_comments($account);
      return new RedirectResponse($config->get('deactivate_account_redirect'));
    }
  }

  /**
   * Disable the nodes of deactivated user.
   */
  public function deactivate_account_disable_nodes($account) {
    $config = $this->config('deactivate_account.settings');
    if ($config->get('deactivate_account_nodes')) {

      $nids = array();
      $account_uid = $account->id();
      $node_ids = \Drupal::database()->select('node_field_data', 'n')
        ->fields('n', array('uid', 'nid'))
        ->condition('uid', $account_uid)
        ->condition('status', 1)
        ->execute()
        ->fetchAll();

      foreach ($node_ids as $value) {
        $nids[$value->nid] = $value->nid;
      }

      $nodes = node_load_multiple($nids);

      foreach ($nodes as $node) {
        $node->status = 0;
        $node->save();
      }

      \Drupal::database()->update('deactivate_account')
        ->fields(array(
          'node_data' => serialize($nids),
        ))
        ->condition('uid', $account_uid)
        ->execute();
    }
  }

  /**
   * Disable the comments of deactivated user.
   */
  function deactivate_account_disable_comments($account) {
    $config = $this->config('deactivate_account.settings');
    if ($config->get('deactivate_account_comments')) {
      $account_id = $account->id();
      $cids = array();

      $comment_ids = \Drupal::database()->select('comment_field_data', 'n')
        ->fields('n', array('cid', 'uid'))
        ->condition('uid', $account_id)
        ->condition('status', 1)
        ->execute()
        ->fetchAll();

      foreach ($comment_ids as $value) {
        $cids[$value->cid] = $value->cid;
      }

      foreach($cids as $cid) {
        $comment = Comment::load($cid);
        $comment->status = 0;
        $comment->save();
      }

      // Get a node storage object.
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');

      // Load multiple nodes.
      $comment_storage->loadMultiple($cids);

      foreach ($comment_storage as $comment) {
        $comment->status = 0;
        $comment->save();
      }

      \Drupal::database()->update('deactivate_account')
        ->fields(array(
          'comment_data' => serialize($cids),
        ))
        ->condition('uid', $account_id)
        ->execute();
    }
  }
}
