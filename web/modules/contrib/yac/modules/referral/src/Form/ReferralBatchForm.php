<?php

namespace Drupal\yac_referral\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\yac_referral\ReferralHandlers;
use Drupal\yac_referral\NewSubscriptionEvent;

/**
 * Class ReferralBatchForm.
 *
 * @package Drupal\yac_referral\Form
 * @group yac_referral
 */
class ReferralBatchForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yac_referral.batch_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_referral_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yac_referral.batch_config');
    $form['bulk_affiliate'] = [
      '#type' => 'radios',
      '#title' => $this->t('Edit users role:'),
      '#description' => $this->t('Bulk action that modifies the site`s users give or revoke the affiliate role.'),
      '#options' => [
        'add' => $this->t('Add affiliate role to all users'),
        'remove' => $this->t('Remove affiliate role from all users'),
      ],
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $finished = [];
    $title = '';
    $users = ReferralHandlers::cleanUsersList();
    if (!$form_state->getValue('bulk_affiliate')) {
      return drupal_set_message(t('Choose if you want to add or remove the affiliate role'));
    }
    $this->config('yac_referral.batch_config')
      ->set('bulk_affiliate', $form_state->getValue('bulk_affiliate'))
      ->save();
    // Handles the bulk add of affiliate role to users.
    if ('add' === $form_state->getValue('bulk_affiliate')) {
      // Operations performed on every record during the batch.
      $operations = [];
      $operations[] = ['\Drupal\yac_referral\ReferralBatchHandlers::bulkAddAffiliates', [$users]];
      $title = t('Applying affiliate role to all users');
      $finished[] = ['\Drupal\yac_referral\ReferralBatchHanlers::bulkAddFinished'];
    }
    // Handles the bulk removal of affiliate role from users.
    elseif ('remove' === $form_state->getValue('bulk_affiliate')) {
      // Operations performed on every record during the batch.
      $operations = [];
      $operations[] = ['\Drupal\yac_referral\ReferralBatchHandlers::bulkRemoveAffiliates', [$users]];
      $title = t('Removing affiliate role from all users');
      $finished[] = ['\Drupal\yac_referral\ReferralBatchHanlers::bulkRemoveFinished'];
    }
    // Batch settings.
    $batch = [
      'title' => $title,
      'operations' => $operations,
      'finished' => $finished,
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = [
      $form_state->getValue('bulk_affiliate'),
    ];
  }

}
