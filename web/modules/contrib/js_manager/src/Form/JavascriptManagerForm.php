<?php

namespace Drupal\js_manager\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class JavascriptForm.
 *
 * @package Drupal\js_manager\Form
 */
class JavascriptManagerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $javascript = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $javascript->label(),
      '#description' => $this->t("Name for the Javascript set."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $javascript->id(),
      '#machine_name' => [
        'exists' => '\Drupal\js_manager\Entity\Javascript::load',
      ],
      '#disabled' => !$javascript->isNew(),
    ];

    $form['js_type'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#options' => [
        'external' => 'External',
        'inline' => 'Inline',
      ],
      '#description' => 'Type of JavaScript',
      '#default_value' => $javascript->getJsType(),
    ];
    // External Fields.
    $form['external'] = [
      '#type' => 'fieldset',
      '#title' => t('External'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="js_type"]' => ['value' => 'external'],
        ],
        'invisible' => [
          ':input[name="js_type"]' => ['value' => 'inline'],
        ],
      ],
    ];
    $form['external']['external_js'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => 'External JavaScript URL',
      '#default_value' => $javascript->getExternalJs(),
    ];
    $form['external']['external_js_async'] = [
      '#type' => 'checkbox',
      '#title' => t('Load Asynchronously'),
      '#description' => 'Load external JavaScript Asynchronously',
      '#default_value' => $javascript->getExternalJsAsync(),
    ];
    // Inline Fields.
    $form['inline'] = [
      '#type' => 'fieldset',
      '#title' => t('Inline'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="js_type"]' => ['value' => 'inline'],
        ],
        'invisible' => [
          ':input[name="js_type"]' => ['value' => 'external'],
        ],
      ],
    ];
    $form['inline']['inline_js'] = [
      '#type' => 'textarea',
      '#title' => t('Snippet'),
      '#description' => 'Inline JavaScript snippet',
      '#default_value' => $javascript->getInlineJs(),
    ];
    $form['exclude_admin'] = [
      '#type' => 'checkbox',
      '#title' => t('Exclude on admin paths'),
      '#description' => 'Exclude on admin paths',
      '#default_value' => $javascript->excludeAdmin(),
    ];
    $form['weight'] = [
      '#type' => 'textfield',
      '#maxlength' => 5,
      '#size' => 5,
      '#title' => t('Weight'),
      '#description' => 'Weight to control the order of scripts.',
      '#default_value' => $javascript->getWeight(),
    ];
    $form['scope'] = [
      '#type' => 'select',
      '#title' => t('Scope'),
      '#options' => [
        'header' => 'Header',
        'footer' => 'Footer',
      ],
      '#description' => 'Where the script should be added.',
      '#default_value' => $javascript->getScope(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %id Javascript.', [
          '%id' => $this->entity->id(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Updated the %id Javascript.', [
          '%id' => $this->entity->id(),
        ]));
    }

    // Invalidate render cache so that new scripts appear.
    Cache::invalidateTags(['rendered']);

    // Back to listing.
    $form_state->setRedirect('entity.javascript.list');
  }

}
