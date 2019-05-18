<?php

namespace Drupal\abookings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper; // Buggy
// use \Symfony\Component\Validator\Constraints\UrlValidator;

/**
 * Class BookingSettingsForm.
 *
 * @package Drupal\abookings\Form
 */
class BookingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'abookings.bookingsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'booking_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bookables = get_bookables();
    $bookables_options = [
      '0' => '- none selected -',
    ];
    foreach ($bookables as $nid => $node) {
      $bookables_options[$nid] =  $node->label();
    }
    // kint($bookables_options, '$bookables_options');

    $config = $this->config('abookings.bookingsettings');

    $form['bookable'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Site bookable unit'),
      '#description' => $this->t('(Optional) If this site represents just one bookable unit (eg. a guesthouse), then provide the id of the bookable unit that this site represents. If left blank, the guest can choose the bookable unit they would like to book on the Book page.'),
      '#default_value' => $config->get('bookable'),
    ];

    $form['deposit_reminder_hours'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 720,
      '#title' => $this->t('Deposit reminder hours'),
      '#description' => $this->t('Number of hours after booking has been made that the guests will be reminded to pay the deposit. Eg. 24.'),
      '#default_value' => $config->get('deposit_reminder_hours'),
    ];
    $form['deposit_hours'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 720,
      '#title' => $this->t('Deposit due hours'),
      '#description' => $this->t('Number of hours after booking has been made within which guests must pay the deposit. Eg. 48.'),
      '#default_value' => $config->get('deposit_hours'),
    ];
    $form['deposit_rate'] = [
      '#type' => 'number',
      '#title' => $this->t('Deposit rate'),
      '#description' => $this->t('Percentage of total cost that guests must pay to secure their booking. Eg. 50.'),
      '#default_value' => $config->get('deposit_rate'),
    ];
    $form['prearrival_hours'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 240,
      '#title' => $this->t('Pre-arrival hours'),
      '#description' => $this->t('Number of hours before the guests’ check-in time that the pre-arrival email should be sent to guests. Eg. 24.'),
      '#default_value' => $config->get('prearrival_hours'),
    ];
    $form['settings_months'] = [
      '#type' => 'number',
      '#title' => $this->t('Months in settings'),
      '#min' => 6,
      '#max' => 60,
      '#description' => $this->t('Number of months shown by the charts on the Settings page. Min: 6. Max: 60. Eg. 24.'),
      '#default_value' => $config->get('settings_months'),
    ];
    $form['notifn_emails_addresses'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification email addresses'),
      '#description' => $this->t('Email addresses that booking notification emails should be sent to. Separate multiple email addresses by just a comma.'),
      '#default_value' => $config->get('notifn_emails_addresses'),
    ];
    $form['backend_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backend URL'),
      '#maxlength' => 64,
      '#size' => 64,
      '#description' => $this->t('(Optional) The address of the website that bookings should be sent to for processing and storage. If left blank, the bookings will be stored on this website.<br>'
        . '<strong>Note:</strong> Must not end in a trailing slash ("/").'),
      '#required' => FALSE,
      '#default_value' => $config->get('backend_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate site bookable unit
    $bookable_nid = $form_state->getValue('bookable');
    if ($bookable_nid) {
      $bookable = node_load($bookable_nid);
      if (! $bookable) {
        $form_state->setErrorByName('bookable',
          $this->t("No bookable unit was found with that ID."));
      }
    }

    // Validate notification email addresses

    $recipients_string = $form_state->getValue('notifn_emails_addresses');
    $recipients = explode(',', $recipients_string);
    foreach ($recipients as $index => $address) {
      if (\Drupal::service('email.validator')->isValid($address) === FALSE) {
        $form_state->setErrorByName('notifn_emails_addresses',
          $this->t("Email address '%address' is not valid.", array('%address' => $address)));
      }
    }

    // Validate backend URL

    $backend_url = $form_state->getValue('backend_url');
    if ($backend_url && UrlHelper::isValid($backend_url) === FALSE) {
    // $urlValidator = new UrlValidator();
    // $violations = $urlValidator->validate($backend_url, new Url());
    // if ($backend_url && count($violations) !== 0) {
      $form_state->setErrorByName('backend_url',
        $this->t("URL '%url' is not valid.", array('%url' => $backend_url)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('abookings.bookingsettings')
      ->set('bookable',                 $form_state->getValue('bookable'))
      ->set('deposit_reminder_hours',   $form_state->getValue('deposit_reminder_hours'))
      ->set('deposit_hours',            $form_state->getValue('deposit_hours'))
      ->set('deposit_rate',             $form_state->getValue('deposit_rate'))
      ->set('prearrival_hours',         $form_state->getValue('prearrival_hours'))
      ->set('settings_months',          $form_state->getValue('settings_months'))
      ->set('notifn_emails_addresses',  $form_state->getValue('notifn_emails_addresses'))
      ->set('backend_url',              $form_state->getValue('backend_url'))
      ->save();
  }

}
