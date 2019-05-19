<?php

/**
 * @file
 * Contains \Drupal\oauth\Form\OAuthAddConsumerForm.
 */

namespace Drupal\oauth\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add OAuth consumers.
 */
class OAuthAddConsumerForm extends FormBase {

  const NAME = 'oauth_add_consumer_form';

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserData
   */
  protected $user_data;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $container->get('current_user');

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = $container->get('user.data');
    return new static($current_user, $user_data);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return static::NAME;
  }

  /**
   * {@inheritdoc}
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user service.
   * @param \Drupal\user\UserDataInterface $user_data
   *  The user data service.
   */
  public function __construct(AccountProxyInterface $account, UserDataInterface $user_data) {
    $this->account = $account;
    $this->user_data = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    );
    $form['uid'] = array(
      '#type' => 'hidden',
      '#value' => $user->id(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $consumer_key = user_password(32);
    $consumer_secret  = user_password(32);
    $key_hash = sha1($consumer_key);
    $uid = $form_state->getValue('uid');
    $consumer = array(
      'consumer_secret' => $consumer_secret,
      'key_hash' => $key_hash,
    );
    $this->user_data->set('oauth', $uid, $consumer_key, $consumer);
    drupal_set_message($this->t('Added a new consumer.'));
    Cache::invalidateTags(['oauth:' . $uid]);
    $form_state->setRedirect('oauth.user_consumer', array('user' => $uid));
  }

}
