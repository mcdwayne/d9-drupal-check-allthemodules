<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\optit\Optit\Optit;

/**
 * Subscribe to an interest form.
 */
class SubscriptionInterestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_subscriptions_interest_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL, $interest_id = NULL) {

    $optit = Optit::create();

    // First load all phone numbers subscribed to a given keyword.
    $keyword_subscriptions = $optit->subscriptionsGet($keyword_id);
    $phones = array();
    foreach ($keyword_subscriptions as $keyword_subscription) {
      $phones[$keyword_subscription->get('phone')] = $keyword_subscription->get('phone');
    }

    // Then remove all phone numbers that were already subscribed to a given interest.
    $interest_subscriptions = $optit->interestGetSubscriptions($interest_id);
    foreach ($interest_subscriptions as $interest_subscription) {
      unset($phones[$interest_subscription->get('phone')]);
    }

    // If there are not available members, do not display the form.
    if (count($phones) == 0) {
      $form['error_message'] = [
        '#markup' => $this->t('List of available phone numbers is empty. Please subscribe a member to the parent keyword.'),
      ];

      return $form;
    }

    $form['keyword_id'] = [
      '#type' => 'value',
      '#value' => $keyword_id,
    ];
    $form['interest_id'] = [
      '#type' => 'value',
      '#value' => $interest_id,
    ];
    $form['phone'] = [
      '#title' => t('Phone number'),
      '#description' => t('The list contains phone numbers of members subscribed to a given keyword.'),
      '#type' => 'select',
      '#options' => $phones,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();

    if ($optit->interestSubscribe($form_state->getValue('interest_id'), $form_state->getValue('phone'))) {
      drupal_set_message($this->t('Subscription saved successfully.'));
      if (!isset($_GET['destination'])) {
        // default redirection could be better...
        $form_state->setRedirect('optit.structure_keywords_interests_subscriptions', [
          'keyword_id' => $form_state->getValue('keyword_id'),
          'interest_id' => $form_state->getValue('interest_id'),
        ]);
      }
    }
    else {
      $form_state->setRebuild();
      $form_state->setError($form, $this->t('Subscription could not be saved. Check error logs for details.'));
    }
  }
}
