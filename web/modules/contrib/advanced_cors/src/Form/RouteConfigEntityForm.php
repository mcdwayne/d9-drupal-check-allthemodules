<?php

namespace Drupal\advanced_cors\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RouteConfigEntityForm.
 */
class RouteConfigEntityForm extends EntityForm {

  protected $patterns;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->patterns = \Drupal::service('advanced_cors.patterns_cache'); // @codingStandardsIgnoreLine
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\advanced_cors\Entity\RouteConfigEntityInterface $route_config */
    $route_config = $this->entity;

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Status'),
      '#maxlength' => 255,
      '#default_value' => $route_config->status(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $route_config->label(),
      '#description' => $this->t('Name for the Route configuration.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $route_config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\advanced_cors\Entity\RouteConfigEntity::load',
      ],
      '#disabled' => !$route_config->isNew(),
    ];

    $form['patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Patterns'),
      '#default_value' => $route_config->get('patterns'),
      '#description' => $this->t('New line - new pattern.'),
      '#required' => TRUE,
    ];

    $form['allowed_headers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed headers'),
      '#default_value' => $route_config->get('allowed_headers'),
      '#description' => $this->t('Example: * or X-Csrf-Token, Accept, Content-type, Origin'),
    ];

    $form['allowed_methods'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed methods'),
      '#default_value' => $route_config->get('allowed_methods'),
      '#description' => $this->t('Example: * or POST, GET, OPTIONS'),
    ];

    $form['allowed_origins'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed origins'),
      '#default_value' => $route_config->get('allowed_origins'),
      '#description' => $this->t('Example: * or https://developer.mozilla.org:80'),
    ];

    $form['exposed_headers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exposed headers'),
      '#default_value' => $route_config->get('exposed_headers'),
    ];

    $form['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max age (seconds)'),
      '#default_value' => $route_config->get('max_age'),
    ];

    $form['supports_credentials'] = [
      '#type' => 'radios',
      '#title' => $this->t('Supports credentials'),
      '#options' => [
        '' => $this->t('hidden'),
        'true' => $this->t('True'),
        'false' => $this->t('False'),
      ],
      '#default_value' => $route_config->get('supports_credentials') ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\advanced_cors\Entity\RouteConfigEntityInterface $route_config */
    $route_config = $this->entity;
    $status = $route_config->save();
    $this->patterns->resetCache();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Route configuration.', [
          '%label' => $route_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Route configuration.', [
          '%label' => $route_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($route_config->toUrl('collection'));
  }

}
