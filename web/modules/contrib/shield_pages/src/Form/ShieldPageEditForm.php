<?php

namespace Drupal\shield_pages\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit form for shield pages.
 */
class ShieldPageEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\shield_pages\ShieldPageInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    $passwords = [];
    foreach (explode(PHP_EOL, $form_state->getValue('passwords')) as $password) {
      if ($trim_password = trim($password)) {
        $passwords[] = $trim_password;
      }
    }

    $entity->setPasswords($passwords);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => 'Drupal\shield_pages\Entity\ShieldPage::load',
      ],
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relative Path'),
      '#description' => $this->t('Example paths are blog for the blog page and blog/* for every personal blog.'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getPath(),
      '#required' => TRUE,
    ];

    $form['passwords'] = [
      '#title' => $this->t('Specify Passwords'),
      '#description' => $this->t("Specify the passwords one per line. Don'n use spaces at the beginning and end of the password, because they will be trimmed."),
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, $this->entity->getPasswords()),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path = $form_state->getValue('path');

    if (!\Drupal::service('path.validator')->isValid($path)) {
      $form_state->setErrorByName('path', t("The path '@path' is either invalid or you do not have access to it.", ['@path' => $path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    drupal_set_message($this->t('Shield page @label saved.', ['@label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }
}
