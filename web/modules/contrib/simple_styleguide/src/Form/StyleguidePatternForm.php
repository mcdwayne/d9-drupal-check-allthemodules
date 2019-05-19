<?php

namespace Drupal\simple_styleguide\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StyleguidePatternForm.
 *
 * @package Drupal\simple_styleguide\Form
 */
class StyleguidePatternForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $styleguide_pattern = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $styleguide_pattern->label(),
      '#description' => $this->t("Label for the Styleguide pattern."),
      '#required' => TRUE,
    ];

    $form['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pattern'),
      '#rows' => 15,
      '#default_value' => $styleguide_pattern->pattern,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $styleguide_pattern->id(),
      '#machine_name' => [
        'exists' => '\Drupal\simple_styleguide\Entity\StyleguidePattern::load',
      ],
      '#disabled' => !$styleguide_pattern->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $styleguide_pattern = $this->entity;
    $status = $styleguide_pattern->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Styleguide pattern.', [
          '%label' => $styleguide_pattern->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Styleguide pattern.', [
          '%label' => $styleguide_pattern->label(),
        ]));
    }
    $form_state->setRedirectUrl($styleguide_pattern->toUrl('collection'));
  }

}
