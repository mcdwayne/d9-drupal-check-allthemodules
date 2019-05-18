<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flags\Entity\FlagMapping;

/**
 * Class CountryMappingEditForm
 *
 * Provides the edit form for our FlagMapping entity.
 *
 * @package Drupal\flags\Form
 *
 * @ingroup flags
 */
class CountryMappingEditForm extends CountryMappingForm {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update mapping');
    return $actions;
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var FlagMapping $mapping */
    $mapping = $this->entity;

    // Unfortunately countries are indexed with uppercase letters
    // se we make sure our ids are correct.
    $id = strtoupper($mapping->getSource());

    $form['title'] = [
      '#type' => 'item',
      '#markup' => isset($this->countries[$id]) ? $this->countries[$id] : $id,
    ];

    $form = parent::buildForm($form, $form_state);

    // Once created, source can not be edited.
    $form['source'] = [
      '#type' => 'value',
      '#value' => $mapping->getSource(),
    ];

    return $form;
  }


}
