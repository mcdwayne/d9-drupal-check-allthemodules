<?php

/**
 * @file
 * Contains \Drupal\oauth\Form\OAuthDeleteConsumerForm.
 */

namespace Drupal\oauth\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an oauth_consumer deletion confirmation form.
 */
class OAuthDeleteConsumerForm extends ConfirmFormBase implements ContainerInjectionInterface {

  const NAME = 'oauth_delete_consumer_form';

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
   * Factory.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DIC.
   *
   * @return static
   *   The form instance.
   */
  public static function create(ContainerInterface $container) {

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $container->get('current_user');

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = $container->get('user.data');

    return new static($current_user, $user_data);
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface
   *
   * @param \Drupal\user\UserDataInterface
   */
  public function __construct(AccountProxyInterface $account, UserDataInterface $user_data) {
    $this->account = $account;
    $this->user_data = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return static::NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this OAuth consumer?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('oauth.user_consumer', ['user' => \Drupal::currentUser()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return static::NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL, $key = NULL) {
    $form['key'] = array(
      '#type' => 'hidden',
      '#value' => $key,
    );

    $form['uid'] = array(
      '#type' => 'hidden',
      '#value' => $user->id()
    );

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $key = $values['key'];
    $uid = $values['uid'];
    $this->user_data->delete('oauth', $uid, $key);
    drupal_set_message($this->t('OAuth consumer deleted.'));
    Cache::invalidateTags(['oauth:' . $uid]);
    $form_state->setRedirect('oauth.user_consumer', array('user' => $form_state->getValue('uid')));
  }

}
