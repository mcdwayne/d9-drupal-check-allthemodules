<?php

namespace Drupal\simple_megamenu\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SimpleMegaMenuTypeForm.
 *
 * @package Drupal\simple_megamenu\Form
 */
class SimpleMegaMenuTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface $simple_mega_menu_type */
    $simple_mega_menu_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $simple_mega_menu_type->label(),
      '#description' => $this->t("Label for the Simple mega menu type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $simple_mega_menu_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\simple_megamenu\Entity\SimpleMegaMenuType::load',
      ],
      '#disabled' => !$simple_mega_menu_type->isNew(),
    ];

    $options = menu_ui_get_menus();
    $form['targetMenu'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Target menu'),
      '#default_value' => $simple_mega_menu_type->getTargetMenu(),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t("Select the menus on which use this mega menu type."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $simple_mega_menu_type = $this->entity;
    $status = $simple_mega_menu_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Simple mega menu type.', [
          '%label' => $simple_mega_menu_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Simple mega menu type.', [
          '%label' => $simple_mega_menu_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($simple_mega_menu_type->toUrl('collection'));
  }

}
