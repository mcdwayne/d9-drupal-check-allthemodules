<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Form\LinkTypeForm.
 */

namespace Drupal\colossal_menu\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LinkTypeForm.
 *
 * @package Drupal\colossal_menu\Form
 */
class LinkTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $colossal_menu_link_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $colossal_menu_link_type->label(),
      '#description' => $this->t("Label for the Link type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $colossal_menu_link_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\colossal_menu\Entity\LinkType::load',
      ],
      '#disabled' => !$colossal_menu_link_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $colossal_menu_link_type = $this->entity;
    $status = $colossal_menu_link_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Link type.', [
          '%label' => $colossal_menu_link_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Link type.', [
          '%label' => $colossal_menu_link_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($colossal_menu_link_type->urlInfo('collection'));
  }

}
