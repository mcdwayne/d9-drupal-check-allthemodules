<?php

namespace Drupal\pusher_integration\Form;

use Drupal\Core\Entity\EntityForm;
// Use Drupal\pusher_integration\CaptchaPointInterface;.
use Drupal\Core\Form\FormStateInterface;

/**
 * Entity Form to edit CAPTCHA points.
 */
class ChannelPathMapForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    module_load_include('inc', 'pusher_integration');

    /* @var CaptchaPointInterface $captchaPoint */
    $mapEntry = $this->entity;

    // Support to set a default map_id through a query argument.
    $request = \Drupal::request();
    if ($mapEntry->isNew() && !$mapEntry->id() && $request->query->has('mapId')) {
      $mapEntry->set('mapId', $request->query->get('mapId'));
      $mapEntry->set('channelName', $request->query->get('channelName'));
      $mapEntry->set('pathPattern', $request->query->get('pathPattern'));
    }

    $form['mapId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entry ID/Name'),
      '#default_value' => $mapEntry->getMapId(),
      '#required' => TRUE,
    ];

    $form['channelName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pusher Channel Name'),
      '#default_value' => $mapEntry->getChannelName(),
      '#required' => TRUE,
    ];

    $form['pathPattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path Pattern'),
      '#default_value' => $mapEntry->getPathPattern(),
      '#required' => TRUE,
    ];

    $form['mapId'] = [
      '#type' => 'machine_name',
      '#default_value' => $mapEntry->id(),
      '#machine_name' => [
        'exists' => 'channel_path_map_load',
      ],
      '#disable' => !$mapEntry->isNew(),
      '#required' => TRUE,
    ];

    // Select widget for CAPTCHA type.
    // $form['captchaType'] = [
    // '#type' => 'select',
    // '#title' => t('Challenge type'),
    // '#description' => t('The CAPTCHA type to use for this form.'),
    // '#default_value' => ($mapEntry->getCaptchaType() ?: $this->config('captcha.settings')->get('default_challenge')),
    // '#options' => _captcha_available_challenge_types(),
    // ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var ChannelPathMap $mapEntry */
    $mapEntry = $this->entity;
    // Echo '<pre>'; print_r($mapEntry); echo '</pre>';.
    $status = $mapEntry->save();

    // $e = \Drupal::entityTypeManager()->getStorage('channel_path_map')->loadMultiple();
    // echo '<pre>'; print_r($e); echo '</pre>';
    // exit;.
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Channel-Path-Map for %mapId form was created.', [
        '%mapId' => $mapEntry->getMapId(),
      ]));
    }
    else {
      drupal_set_message($this->t('Channel-Path-Map for %mapId form was updated.', [
        '%mapId' => $mapEntry->getMapId(),
      ]));
    }
    $form_state->setRedirect('channel_path_map.list');
  }

}
