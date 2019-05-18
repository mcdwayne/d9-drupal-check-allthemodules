<?php

namespace Drupal\token_custom\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\BundleEntityFormBase;

/**
 * Configure custom settings for this site.
 */
class TokenCustomTypeForm extends BundleEntityFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'token_custom_type_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'token_custom.settings',
    ];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $token_type = $this->entity;;

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Token type's name"),
      '#description' => $this->t("The token type's readable name"),
      '#default_value' => $token_type->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['machineName'] = [
      '#type' => 'machine_name',
      '#title' => $this->t("Token type's machine name"),
      '#description' => $this->t('A unique machine-readable name for this token. It must only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => $token_type->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => '\Drupal\token_custom\Entity\TokenCustomType::load',
        'replace' => '-',
        'replace_pattern' => '[^a-z0-9\-]+',
      ],
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token description'),
      '#description' => $this->t("The token type's description."),
      '#default_value' => $token_type->getDescription(),
      '#required' => TRUE,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The token_custom type %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The token_custom type %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $entity->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('token_custom')->notice('Added token_custom type %name.', $context);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
