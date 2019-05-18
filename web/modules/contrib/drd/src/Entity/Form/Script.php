<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Entity\Script as ScriptEntity;

/**
 * Class Script.
 *
 * @package Drupal\drd\Form
 */
class Script extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\drd\Entity\ScriptInterface $script */
    $script = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $script->label(),
      '#description' => $this->t("Label for the Script."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this script instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$script->isNew() ? $script->id() : '',
      '#machine_name' => [
        'exists' => '\Drupal\drd\Entity\Script::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$script->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => ScriptEntity::getSelectList(FALSE),
      '#default_value' => $script->type(),
    ];
    $form['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code'),
      '#default_value' => $script->code(),
      '#description' => $this->t('Script code without any decoration.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $script = $this->entity;
    $status = $script->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Script.', [
          '%label' => $script->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Script.', [
          '%label' => $script->label(),
        ]));
    }
    $form_state->setRedirectUrl($script->toUrl('collection'));
  }

}
