<?php

namespace Drupal\simple_redirect\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PathElement;

/**
 * Class SimpleRedirectForm.
 *
 * @package Drupal\simple_redirect\Form
 */
class SimpleRedirectForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $simple_redirect = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $simple_redirect->label(),
      '#description' => $this->t("Title of the redirect action."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $simple_redirect->id(),
      '#machine_name' => [
        'exists' => '\Drupal\simple_redirect\Entity\SimpleRedirect::load',
      ],
      '#disabled' => !$simple_redirect->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */
    $form['from'] = array(
      '#type' => 'path',
      '#title' => $this->t('From'),
      '#default_value' => $simple_redirect->getFrom(),
      '#required' => TRUE,
      '#description' => $this->t('From url - example "/node/1"'),
      '#convert_path' => PathElement::CONVERT_NONE,
    );
    $form['to'] = array(
      '#type' => 'path',
      '#title' => $this->t('To'),
      '#default_value' => $simple_redirect->getTo(),
      '#required' => TRUE,
      '#description' => $this->t('To url - example "/node/2"'),
      '#convert_path' => PathElement::CONVERT_NONE,
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fromUrl = $form_state->getValue('from');
    if ($fromUrl && \Drupal::pathValidator()->isValid($fromUrl)) {
      if (substr($fromUrl, 0, 1) != '/') {
        $form_state->setErrorByName('from', $this->t('The path should start with /.'));
      }
//      $form_id = $form_state->getValue('form_id');
//      $conf = \Drupal::entityTypeManager()->getStorage('simple_redirect')->loadByProperties(['from' => $fromUrl]);
//      $conf_count = count($conf);
//      dsm($conf_count);
//      if($form_id == 'simple_redirect_add_form' && $conf_count == 0) {
//        $form_state->setErrorByName('from', $this->t('Add This url had already an other redirect defined..!'));
//      }
//      if ($form_id == 'simple_redirect_edit_form' && $conf_count == 1) {
//        $form_state->setErrorByName('from', $this->t('Edit This url had already an other redirect defined..!'));
//      }
    }
    $toUrl = $form_state->getValue('to');
    if ($form_state->getValue('to') && \Drupal::pathValidator()->isValid($toUrl)) {
      if (substr($toUrl, 0, 1) != '/') {
        $form_state->setErrorByName('to', $this->t('The path should start with /.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $simple_redirect = $this->entity;
    $status = $simple_redirect->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Simple Redirect.', [
          '%label' => $simple_redirect->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Simple Redirect.', [
          '%label' => $simple_redirect->label(),
        ]));
    }
    $form_state->setRedirectUrl($simple_redirect->toUrl('collection'));
  }

}
