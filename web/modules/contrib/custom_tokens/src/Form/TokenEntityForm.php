<?php

namespace Drupal\custom_tokens\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form for adding and editing custom tokens.
 */
class TokenEntityForm extends EntityForm {

  /**
   * The entity the form is the subject of.
   *
   * @var \Drupal\custom_tokens\Entity\TokenEntity
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $token = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token Name'),
      '#maxlength' => 255,
      '#default_value' => $token->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $token->id(),
      '#machine_name' => [
        'exists' => '\Drupal\custom_tokens\Entity\TokenEntity::load',
      ],
      '#disabled' => !$token->isNew(),
    ];
    $form['tokenName'] = [
      '#title' => $this->t('Token'),
      '#type' => 'textfield',
      '#field_prefix' => '[',
      '#field_suffix' => ']',
      '#default_value' => $token->getTokenName(),
    ];
    $form['tokenValue'] = [
      '#title' => $this->t('Value'),
      '#type' => 'textfield',
      '#default_value' => $token->getTokenValue(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.token.collection');
    drupal_set_message('Custom token was successfully saved.');
    return parent::save($form, $form_state);
  }

}
