<?php

namespace Drupal\google_analytics_counter\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_analytics_counter\GoogleAnalyticsCounterHelper;

/**
 * Class GoogleAnalyticsCounterRevokeForm.
 *
 * @package Drupal\google_analytics_counter\Form
 */
class GoogleAnalyticsCounterRevokeForm extends ConfirmFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_counter_admin_revoke';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to revoke authentication?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // todo: Send the user back to the form from which he came.
    return new Url('google_analytics_counter.admin_auth_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // The number of hours it will take to reindex the site.
    return $this->t('Clicking <strong>Yes</strong> means you will have to reauthenticate with Google in order to get new data. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Revoke the state values.
    GoogleAnalyticsCounterHelper::gacDeleteState();

    // Set redirect.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
