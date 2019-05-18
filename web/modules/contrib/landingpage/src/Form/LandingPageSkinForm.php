<?php

namespace Drupal\landingpage\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Class LandingPageSkinForm.
 *
 * @package Drupal\landingpage\Form
 */
class LandingPageSkinForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $landingpage_skin = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $landingpage_skin->label(),
      '#description' => $this->t("Label for the LandingPage Class."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#default_value' => $landingpage_skin->id(),
      '#disabled' => !$landingpage_skin->isNew(),
      '#machine_name' => array(
        'source' => array('label'),
        'exists' => '\Drupal\landingpage\Entity\LandingPageSkin::load',
      ),
      '#description' => $this->t("test."),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $landingpage_skin = $this->entity;
    $status = $landingpage_skin->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label LandingPageSkin.', [
          '%label' => $landingpage_skin->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label LandingPageSkin.', [
          '%label' => $landingpage_skin->label(),
        ]));
    }
    $form_state->setRedirectUrl($landingpage_skin->urlInfo('collection'));
  }

}
