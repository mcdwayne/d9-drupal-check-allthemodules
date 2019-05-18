<?php

namespace Drupal\jg_leaderboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\NullLockBackend;
use GuzzleHttp\Psr7\Request;
use Drupal\jg_leaderboard\Event;

/**
 * Class CreateEventForms
 *
 * @package Drupal\jg_leaderboard\Form
 */
class CreateEventForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jg_leaderboard.admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jg_leaderboard.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config                    = $this->config('jg_leaderboard.settings');
    $form['api_key']           = [
      '#type'     => 'textfield',
      '#title'    => t('API Key'),
      '#required' => TRUE
    ];
    $form['user_name']         = [
      '#type'     => 'textfield',
      '#title'    => $this->t('User name'),
      '#required' => TRUE
    ];
    $form['password']          = [
      '#type'     => 'password',
      '#title'    => 'password',
      '#required' => TRUE
    ];
    $form['envirnoment']       = [
      '#type'    => 'select',
      '#title'   => $this->t('Select Envirnoment'),
      '#options' => [
        'https://api-sandbox.justgiving.com/' => $this->t('Sandbox'),
        'https://api.justgiving.com/'         => $this->t('Production'),
      ],
    ];
    $form['actions']['#type']  = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Create Event'),
      '#button_type' => 'primary',
    ];
    $form['event_name']        = [
      '#type'     => 'textfield',
      '#title'    => t('Event Name'),
      '#required' => TRUE
    ];
    // @todo 165 words for description
    $form['event_description']     = [
      '#type'        => 'textfield',
      '#title'       => t('Event Description'),
      '#required'    => TRUE,
      '#description' => t('Maximum allowed is 165 words.'),
    ];
    $form['event_start_date']      = [
      '#type'     => 'date',
      '#title'    => t('Event Start Date'),
      '#required' => TRUE
    ];
    $form['event_completion_date'] = [
      '#type'     => 'date',
      '#title'    => t('Event Completion Date'),
      '#required' => TRUE
    ];
    $form['event_expiry_date']     = [
      '#type'     => 'date',
      '#title'    => t('Event Expiry Date'),
      '#required' => TRUE
    ];
    $form['event_location']        = [
      '#type'  => 'textfield',
      '#title' => t('Event Location'),
    ];
    $form['event_type']            = [
      '#type'     => 'select',
      '#title'    => $this->t('Event Type'),
      '#options'  => [
        $this->t('OtherCelebration'),
        $this->t('Running_Marathons'),
        $this->t('Treks'),
        $this->t('Swimming'),
        $this->t('Wedding'),
        $this->t('InMemory'),
        $this->t('Triathlons'),
        $this->t('Parachuting_Skydives'),
        $this->t('NewYearsResolutions'),
        $this->t('Christmas'),
        $this->t('OtherPersonalChallenge'),
        $this->t('CharityAppeal'),
        $this->t('IndividualAppeal'),
        $this->t('CompanyAppeal'),
        $this->t('OtherPersonalChallenge'),
        $this->t('CharityAppeal'),
        $this->t('IndividualAppeal'),
        $this->t('CompanyAppeal'),
        $this->t('PersonalRunning_Marathons'),
        $this->t('PersonalTreks'),
        $this->t('PersonalWalks'),
        $this->t('PersonalSwimming'),
        $this->t('PersonalTriathlons'),
        $this->t('PersonalParachuting_Skydives'),
        $this->t('Streaming_Gaming'),
        $this->t('PersonalStreaming_Gaming'),
      ],
      '#required' => TRUE
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = $form_state->get('response');
    dsm('Event Created. This is the EventID: ' . $response->id);
    dsm('Event Uri is : ' . $response->next->uri);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $client                = [];
    $client['envirnoment'] = $values['envirnoment'];
    $client['api_key']     = $values['api_key'];
    $client['api_version'] = "1";
    $client['password']    = "";

    $event                      = new Event($client);
    $event->eventName           = $values['event_name'];
    $event->eventDescription    = $values['event_description'];
    $event->eventCompletionDate = $values['event_completion_date'];
    $event->eventExpiryDate     = $values['event_expiry_date'];
    $event->eventStartDate      = $values['event_start_date'];
    $event->eventType           = $values['event_type'];
    $event->eventLocation       = $values['event_location'];
    $event->setUserName($values['user_name']);
    $event->setPassword($values['password']);

    $response = $event->createEvent($event);
    if ($response === "appIdNotFound") {
      $form_state->setErrorByName('', t('We could not make a successful call with those details provided. Please check you API Key and the reset of the details. This is the error status code returned: ') . $response);
    }
    else {
      $form_state->set('response', $response);
    }
  }
}
