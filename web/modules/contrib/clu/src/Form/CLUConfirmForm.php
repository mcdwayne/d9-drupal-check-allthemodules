<?php

namespace Drupal\clu\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a confirm form before ending users session.
 */
class CLUConfirmForm extends ConfirmFormBase {

  /**
   * The user id.
   *
   * @var string
   */
  protected $uid;

  /**
   * Constructs a new Confirm form object.
   *
   * @param string $uid
   *   The user id.
   */
  public function __construct($uid) {
    $this->uid = $uid;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clu_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to end ' . $this->uid . ' user session..!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('End session');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('clu.c_l_u_listing_users');
  }

  /**
   * {@inheritdoc}
   *
   * @param integer $uid
   *   An user id.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uid = '') {
    $this->uid = $uid;
    $user = User::load($this->uid);
    $form['clu'] = [
      '#markup' => t("Are you sure you want to end, '" . $user->getAccountName() . "' user session..! </br>"),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::load($this->uid);
    if ($user) {
      \Drupal::database()->delete('sessions')
        ->condition('uid', $this->uid)
        ->execute();
      drupal_set_message(t('@username ( @userid ) user session has been ended.',
        array(
          '@username' => $user->getAccountName(),
          '@userid' => $user->id(),
        )
      ));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
