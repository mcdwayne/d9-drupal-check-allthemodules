<?php

/**
 * @file
 * Contains \Drupal\user_badges\Form\BadgeTypeForm.
 */

namespace Drupal\user_badges\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BadgeTypeForm.
 *
 * @package Drupal\user_badges\Form
 */
class BadgeTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $badge_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $badge_type->label(),
      '#description' => $this->t("Label for the Badge type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $badge_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\user_badges\Entity\BadgeType::load',
      ),
      '#disabled' => !$badge_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $badge_type = $this->entity;
    $status = $badge_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Badge type.', [
          '%label' => $badge_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Badge type.', [
          '%label' => $badge_type->label(),
        ]));
    }
    $form_state->setRedirectUrl(new Url('badge.settings'));
  }

}
