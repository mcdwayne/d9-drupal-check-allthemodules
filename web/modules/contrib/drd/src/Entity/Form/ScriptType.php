<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ScriptType.
 *
 * @package Drupal\drd\Form
 */
class ScriptType extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\drd\Entity\ScriptTypeInterface $scriptType */
    $scriptType = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $scriptType->label(),
      '#description' => $this->t("Label for the Script Type."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this script type instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$scriptType->isNew() ? $scriptType->id() : '',
      '#machine_name' => [
        'exists' => '\Drupal\drd\Entity\ScriptType::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$scriptType->isNew(),
    ];
    $form['interpreter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Interpreter'),
      '#default_value' => $scriptType->interpreter(),
    ];
    $form['extension'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extension'),
      '#default_value' => $scriptType->extension(),
    ];
    $form['lineprefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Line Prefix'),
      '#default_value' => $scriptType->lineprefix(),
    ];
    $form['prefix'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prefix'),
      '#default_value' => $scriptType->prefix(),
    ];
    $form['suffix'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Suffix'),
      '#default_value' => $scriptType->suffix(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $scriptType = $this->entity;
    $status = $scriptType->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Script.', [
          '%label' => $scriptType->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Script.', [
          '%label' => $scriptType->label(),
        ]));
    }
    $form_state->setRedirectUrl($scriptType->toUrl('collection'));
  }

}
