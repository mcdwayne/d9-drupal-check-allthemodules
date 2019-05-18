<?php

namespace Drupal\gtm_datalayer_forms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gtm_datalayer\Form\DataLayerAddForm;
use Drupal\gtm_datalayer_forms\Plugin\DataLayerProcessorFormBaseInterface;

/**
 * Provides add form for dataLayer form instance forms.
 */
class DataLayerFormAddForm extends DataLayerAddForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\gtm_datalayer_forms\Entity\DataLayerFormInterface $datalayer */
    $datalayer = $this->entity;

    $form = parent::form($form, $form_state);

    $form['#title'] = $this->t('Add dataLayer form');

    $form['form'] = [
      '#title' => $this->t('Form ID'),
      '#type' => 'textfield',
      '#default_value' => $datalayer->getFrom(),
      '#description' => $this->t("The form ID, the '*' character is a wildcard."),
      '#required' => TRUE,
      '#size' => 50,
    ];

    if ($this->moduleHandler->moduleExists('webform')){
      $form['form']['#description'] .= $this->t(' For webform IDs you should use the prefix "webform_submission_" followed by the webform ID.');
    }

    $form['plugin']['#weight'] = 10;
    $form['weight']['#weight'] = 10.1;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('entity.gtm_datalayer_form.edit_form', ['gtm_datalayer_form' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getProcessorPlugins($group = 'form') {
    return parent::getProcessorPlugins($group);
  }

}
