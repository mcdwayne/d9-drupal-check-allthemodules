<?php

namespace Drupal\rate_limits\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rate_limits\Entity\RateLimitConfig;

/**
 * Class RateLimitConfigForm.
 */
class RateLimitConfigForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $rate_limit_config = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rate_limit_config->label(),
      '#description' => $this->t("Label for the Rate Limit Config."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rate_limit_config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\rate_limits\Entity\RateLimitConfig::load',
      ],
      '#disabled' => !$rate_limit_config->isNew(),
    ];

    $form['user_flood_route'] = [
      '#title' => $this->t('User Flood per Route'),
      '#description' => $this->t('Limits are imposed in routes using the Flood service from Drupal core. All routes will be checked for IP based abuse and user based abuse.'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#rate_limit' => $rate_limit_config,
      '#process' => [[$this, 'userFloodElement']],
    ];

    $form['user_flood_global'] = [
      '#title' => $this->t('Global User Flood'),
      '#description' => $this->t('Global limits, all routes on the site matching the tags will increment the global limit. IP based abuse and user based abuse will be checked.'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#rate_limit' => $rate_limit_config,
      '#process' => [[$this, 'userFloodElement']],
    ];

    $tags = $rate_limit_config->get('tags') ?: [];
    $form['tags'] = [
      '#title' => $this->t('Route Tags'),
      '#description' => $this->t('A route with <strong>all</strong> these tags will have this rate limit applied to it. One tag per line.'),
      '#type' => 'textarea',
      '#default_value' => implode("\r\n", $tags),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rate_limit_config = $this->entity;
    $tags_str = $rate_limit_config->get('tags');
    $rate_limit_config->set('tags', array_unique(explode("\r\n", $tags_str)));
    $status = $rate_limit_config->save();

    $messenger = $this->messenger();
    switch ($status) {
      case SAVED_NEW:
        $messenger->addMessage($this->t('Created the %label Rate Limit Config.', [
          '%label' => $rate_limit_config->label(),
        ]));
        break;

      default:
        $messenger->addMessage($this->t('Saved the %label Rate Limit Config.', [
          '%label' => $rate_limit_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($rate_limit_config->toUrl('collection'));
  }

  /**
   * Attach the common user flood information to the element.
   *
   * @param $element
   *   The form element to attach the extra elements to.
   *
   * @return mixed
   *   The processed element.
   */
  public function userFloodElement($element) {
    // Get the user flood information associated to this route.
    $user_flood = $element['#rate_limit']->get($element['#parents'][0]);
    $user_flood = $user_flood ?: $this->configFactory()->get('user.flood')->get();
    $element['uid_only'] = [
      '#title' => $this->t('UID only identifier'),
      '#description' => $this->t('When checked the IP address not used as a factor for the user based checks.'),
      '#type' => 'checkbox',
      '#default_value' => $user_flood['uid_only'],
    ];

    $element['ip_limit'] = [
      '#title' => $this->t('IP Limit'),
      '#type' => 'number',
      '#default_value' => $user_flood['ip_limit'],
      '#required' => TRUE,
    ];

    $element['ip_window'] = [
      '#title' => $this->t('IP Window'),
      '#type' => 'number',
      '#default_value' => $user_flood['ip_window'],
      '#required' => TRUE,
    ];

    $element['user_limit'] = [
      '#title' => $this->t('User Limit'),
      '#type' => 'number',
      '#default_value' => $user_flood['user_limit'],
      '#required' => TRUE,
    ];

    $element['user_window'] = [
      '#title' => $this->t('User Window'),
      '#type' => 'number',
      '#default_value' => $user_flood['user_window'],
      '#required' => TRUE,
    ];
    return $element;
  }

}
