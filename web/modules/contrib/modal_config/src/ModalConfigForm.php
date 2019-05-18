<?php

namespace Drupal\modal_config;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the modal config entity edit forms.
 */
class ModalConfigForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\modal_config\Entity\ModalConfig $entity */
    $entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Configuration name'),
      '#description' => t('Configuration name'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\modal_config\Entity\ModalConfig::load',
        'source' => ['label'],
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ],
      '#default_value' => $entity->id(),
      // This id could be used for menu name.
      '#maxlength' => 23,
    ];
    $form['config_key'] = [
      '#type' => 'select',
      '#title' => t('Type'),
      '#description' => t('Configuration type'),
      '#required' => TRUE,
      '#options' => [
        'RouteName' => 'Route name',
      ],
      '#default_value' => $entity->ConfigKey(),
    ];
    $form['config_value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#description' => t('Configuration value'),
      '#required' => TRUE,
      '#default_value' => $entity->ConfigValue(),
    ];

    $form['actions']['submit']['#value'] = t('Create new configuration');

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    $entity->save();

    if ($is_new) {
      drupal_set_message(t('The %set_name modal configuration has been created.', ['%set_name' => $entity->label()]));
    }
    else {
      drupal_set_message(t('Updated modal configuration name to %set-name.', ['%set-name' => $entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

}
