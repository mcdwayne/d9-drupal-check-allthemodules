<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that creates a subscription.
 * The form is ill-named as the API does not support editing of subscriptions!
 */
class SubscriptionEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_subscriptions_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL) {

    $optit = Optit::create();

    $form['keywordId'] = array(
      "#type" => 'value',
      "#value" => $keyword_id
    );
    $form['phone'] = array(
      '#title' => t('Phone'),
      '#description' => t('Mobile phone number of the member with country code - 1 for U.S. phone numbers. (Phone or member id is required)  Example: 12225551212'),
      '#type' => 'textfield',
    );
    $form['member_id'] = array(
      '#title' => t('Member ID'),
      '#description' => t('Id number of the member (Phone or member id is required)'),
      '#type' => 'textfield',
    );

    $options = array();
    $interests = $optit->interestsGet($keyword_id);
    foreach ($interests as $interest) {
      $options[$interest->get('id')] = $interest->get('name');
    }

    if (count($options) > 0) {
      $form['interest_id'] = array(
        '#title' => t('Interests'),
        '#description' => t('You can limit subscription to certain interests only.'),
        '#type' => 'checkboxes',
        '#options' => $options,
      );
    }
    else {
      $form['interest_id'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }

    $form['first_name'] = array(
      '#title' => t('First name'),
      '#description' => t('First name of the member'),
      '#type' => 'textfield',
    );
    $form['last_name'] = array(
      '#title' => t('Last name'),
      '#description' => t('Last name of the member'),
      '#type' => 'textfield',
    );
    $form['address1'] = array(
      '#title' => t('Address 1'),
      '#type' => 'textfield',
    );
    $form['address2'] = array(
      '#title' => t('Address 2'),
      '#type' => 'textfield',
    );
    $form['city'] = array(
      '#title' => t('City'),
      '#type' => 'textfield',
    );
    $form['state'] = array(
      '#title' => t('State'),
      '#description' => t('Choose a state'),
      '#type' => 'select',
      '#options' => array(
          0 => t('- none -')
        ) + _optit_us_states(),
    );
    $form['zip'] = array(
      '#title' => t('Zip code'),
      '#type' => 'textfield',
    );
    $form['gender'] = array(
      '#title' => t('Gender'),
      '#type' => 'select',
      '#options' => array(
        0 => t('- none -'),
        'male' => t('Male'),
        'female' => t('Female')
      )
    );
    $form['birth_date'] = array(
      '#title' => t('Birth date'),
      '#type' => 'date',
    );
    $form['email_address'] = array(
      '#title' => t('Email address'),
      '#type' => 'textfield',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit')
    );

    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {

    // A subscription has to have either a member_id or phone.
    if (!$form_state->getValue('member_id') && !$form_state->getValue('phone')) {
      $form_state->setErrorByName('member_id', $this->t('Either member ID or phone must be provided.'));
      $form_state->setErrorByName('phone');
    }

    // @todo: validate email;

    // @todo: validate zip;

    // Iterate through interestID's and merge selected ones into a comma separated string.
    $interests = [];
    $selectables = $form_state->getValue('interest_id');
    foreach ($selectables as $selectable) {
      if ($selectable) {
        $interests[] = $selectable;
      }
    }
    $form_state->setValue('interest_id', implode(',', $interests));

    // Set proper birth date format.
    $birth_date = $form_state->getValue('birth_date');
    // This case is probably D7 only!
    // @todo: Make sure you can delete this case. Then delete it.
    if (is_array($birth_date)) {
      $month = $birth_date['month'];
      $day = $birth_date['day'];
      $year = $birth_date['year'];
      if (strlen($month) == 1) {
        $month = '0' . $month;
      }
      if (strlen($day) == 1) {
        $day = '0' . $day;
      }
      $birth_date = $year . $month . $day;
    }
    // Looks like D8's date format is by default: 2017-12-31
    else {
      $birth_date = str_replace('-', '', $birth_date);
    }
    $form_state->setValue('birth_date', $birth_date);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();

    $v = $form_state->getValues();

    if ($optit->subscriptionCreate($v['keywordId'], $v['phone'], $v['member_id'], $v['interest_id'], $v['first_name'], $v['last_name'], $v['address1'], $v['address2'], $v['city'], $v['state'], $v['zip'], $v['gender'], $v['birth_date'], $v['email_address'])) {
      if (!isset($_GET['destination'])) {
        $form_state->setRedirect('optit.structure_keywords_subscriptions', [
          'keyword_id' => $v['keywordId']
        ]);
      }
    }

    else {
      $form_state->setRebuild();
      $form_state->setError($form, $this->t('Subscription could not be saved. Check error logs for details.'));
    }

  }
}
