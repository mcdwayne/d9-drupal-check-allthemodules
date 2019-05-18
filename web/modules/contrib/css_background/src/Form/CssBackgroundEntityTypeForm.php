<?php

namespace Drupal\css_background\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CssBackgroundEntityTypeForm.
 *
 * @package Drupal\css_background\Form
 */
class CssBackgroundEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $css_background_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $css_background_type->label(),
      '#description' => $this->t("Label for the CSS background type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $css_background_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\css_background\Entity\CssBackgroundEntityType::load',
      ],
      '#disabled' => !$css_background_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $css_background_type = $this->entity;
    $status = $css_background_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label CSS background type.', [
          '%label' => $css_background_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label CSS background type.', [
          '%label' => $css_background_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($css_background_type->toUrl('collection'));
  }

}
