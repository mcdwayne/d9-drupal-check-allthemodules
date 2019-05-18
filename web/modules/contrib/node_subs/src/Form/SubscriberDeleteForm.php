<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node_subs\Service\AccountService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SubscriberDeleteForm.
 */
class SubscriberDeleteForm extends ConfirmFormBase {

  private $subscriber_id;

  private $subscriber;

  /**
   * @var \Drupal\node_subs\Service\AccountService
   */
  private $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountService $account_service) {
    $this->account = $account_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('node_subs.account')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_subscriber_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('node_subs.subscribers');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete subscriber?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Do you want to delete subscriber? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $subscriber_id = NULL) {
    $this->subscriber_id = $subscriber_id;
    $this->subscriber = $this->account->load($subscriber_id);
    if (!$this->subscriber) {
      $back_link = \Drupal\Core\Url::fromRoute('node_subs.subscribers')->toString();
      drupal_set_message($this->t('The user does not exist. Get <a href="@link">back to subscribers list</a>', ['@link' => $back_link]), 'warning');
      return [];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(t('Account %name (%email) deleted.', array('%name' => $this->subscriber->name, '%email' => $this->subscriber->email)));
    $form_state->setRedirect('node_subs.subscribers');
    $this->account->delete($this->subscriber);

  }

}
