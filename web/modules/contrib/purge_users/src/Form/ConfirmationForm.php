<?php

namespace Drupal\purge_users\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Class ConfirmationForm.
 *
 * @package Drupal\purge_users\Form
 */
class ConfirmationForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_users_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Purge user confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('purge_users.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to cancel these user accounts?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
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
  public function buildForm(array $form, FormStateInterface $form_state, $accounts = NULL) {
    $form = parent::buildForm($form, $form_state);
    $uids = purge_users_get_user_ids();
    $form['accounts'] = array(
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE,
    );
    $accounts = User::loadMultiple($uids);
    foreach ($accounts as $account) {
      // Prevent user 1 from being canceled.
      if ($account->get('uid')->value <= 1) {
        continue;
      }
      $form['accounts']['uid' . $account->get('uid')->value] = array(
        '#type' => 'markup',
        '#value' => $account->get('uid')->value,
        '#prefix' => '<li>',
        '#suffix' => $account->get('name')->value . " &lt;" . $account->get('mail')->value . "&gt; </li>\n",
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ids = purge_users_get_user_ids();
    // Initialize a batch operation.
    $batch = array(
      'operations' => array(),
      'finished' => 'purge_users_batch_completed',
      'title' => $this->t('Delete users'),
      'init_message' => $this->t('Delete users operation is starting...'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Delete users operation has encountered an error.'),
    );
    // User load multiple to process through batch operation.
    $results = User::loadMultiple($ids);
    foreach ($results as $result) {
      $batch['operations'][] = array('\Drupal\purge_users\Plugin\BatchWorker\BatchWorker::batchworkerpurgeusers', array($result));
    }
    // Batch set.
    batch_set($batch);
  }

}
