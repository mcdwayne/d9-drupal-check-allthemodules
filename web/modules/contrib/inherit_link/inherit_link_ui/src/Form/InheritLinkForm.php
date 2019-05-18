<?php

namespace Drupal\inherit_link_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class InheritLinkForm.
 */
class InheritLinkForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $inherit_link = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $inherit_link->label(),
      '#description' => $this->t("Label for the Inherit link."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $inherit_link->id(),
      '#machine_name' => [
        'exists' => '\Drupal\inherit_link_ui\Entity\InheritLink::load',
      ],
      '#disabled' => !$inherit_link->isNew(),
    ];

    $form['element_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element selector'),
      '#maxlength' => 255,
      '#default_value' => $inherit_link->getElementSelector(),
      '#required' => TRUE,
    ];

    $form['link_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link selector'),
      '#maxlength' => 255,
      '#default_value' => $inherit_link->getLinkSelector(),
      '#required' => TRUE,
    ];

    $form['prevent_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prevent selector'),
      '#maxlength' => 255,
      '#default_value' => $inherit_link->getPreventSelector(),
    ];

    $form['hide_element'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide element'),
      '#default_value' => $inherit_link->getHideElement(),
    ];

    $form['auto_external'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto external'),
      '#default_value' => $inherit_link->getAutoExternal(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $inherit_link = $this->entity;
    $status = $inherit_link->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Inherit link.', [
          '%label' => $inherit_link->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Inherit link.', [
          '%label' => $inherit_link->label(),
        ]));
    }
    $form_state->setRedirectUrl($inherit_link->toUrl('collection'));
  }

}
