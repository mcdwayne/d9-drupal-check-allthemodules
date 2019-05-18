<?php

namespace Drupal\owntracks\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\owntracks\OwnTracksLocationService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OwnTracks user map form.
 */
class OwnTracksUserMapForm extends FormBase {

  /**
   * OwntracksLocationService definition.
   *
   * @var \Drupal\owntracks\OwnTracksLocationService
   */
  protected $ownTracksLocationService;

  /**
   * OwnTracksDateSelectForm constructor.
   *
   * @param \Drupal\owntracks\OwnTracksLocationService $owntracks_location_service
   *   The owntracks location service.
   */
  public function __construct(OwnTracksLocationService $owntracks_location_service) {
    $this->ownTracksLocationService = $owntracks_location_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\owntracks\OwnTracksLocationService $locationService */
    $locationService = $container->get('owntracks.location_service');

    return new static(
      $locationService
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owntracks_map_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $now = new DrupalDateTime();
    $user = $user === NULL ? $this->currentUser() : $user;
    $date = DrupalDateTime::createFromArray([
      'day'   => $form_state->getValue('day', $now->format('j')),
      'month' => $form_state->getValue('month', $now->format('n')),
      'year'  => $form_state->getValue('year', $now->format('Y')),
    ]);
    $tracker_id = $form_state->getValue('tracker_id');
    $ajax = [
      'callback' => [$this, 'reloadForm'],
      'event' => 'change',
      'wrapper' => 'owntracks-map-form-wrapper',
      'progress' => [
        'type' => 'throbber',
        'message' => '',
      ],
    ];

    $form['#prefix'] = '<div id="owntracks-map-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $options = [];

    for ($i = 1; $i <= $date->format('t'); $i++) {
      $options[$i] = $i;
    }

    $form['container']['day'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $options,
      '#default_value' => $date->format('j'),
      '#required' => TRUE,
      '#weight' => -10,
      '#ajax' => $ajax,
    ];

    $options = [];

    for ($i = 1; $i <= 12; $i++) {
      $options[$i] = $i;
    }

    $form['container']['month'] = [
      '#type' => 'select',
      '#title' => $this->t('Month'),
      '#options' => $options,
      '#default_value' => $date->format('n'),
      '#required' => TRUE,
      '#weight' => 0,
      '#ajax' => $ajax,
    ];

    $options = [];

    for ($i = 1978; $i <= $now->format('Y'); $i++) {
      $options[$i] = $i;
    }

    $form['container']['year'] = [
      '#type' => 'select',
      '#title' => $this->t('Year'),
      '#options' => $options,
      '#default_value' => $date->format('Y'),
      '#required' => TRUE,
      '#weight' => 10,
      '#ajax' => $ajax,
    ];

    $form['container']['tracker_id'] = [
      '#type' => 'textfield',
      '#size' => 2,
      '#maxlength' => 2,
      '#title' => $this->t('Tracker ID'),
      '#default_value' => $tracker_id,
      '#required' => FALSE,
      '#weight' => 20,
      '#ajax' => $ajax,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 20,
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    $form['map'] = [
      '#theme' => 'owntracks_map',
      '#track' => $this->ownTracksLocationService->getUserTrack($user, $date, $tracker_id),
      '#weight' => 30,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Reload the form.
   *
   * @return array
   *   The form render array
   */
  public function reloadForm(array &$form) {
    return $form;
  }

}
