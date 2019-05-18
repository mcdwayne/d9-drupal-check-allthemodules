<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flags\Entity\FlagMapping;

/**
 * Class FlagMappingEditForm
 *
 * Provides the edit form for our FlagMapping entity.
 *
 * @package Drupal\flags_languages\Form
 *
 * @ingroup flags_languages
 */
class LanguageMappingEditForm extends LanguageConfigEntityFormBase {

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
    $allLanguages = $this->languageManager->getAllDefinedLanguages();
    $id = $mapping->getSource();

    $row['language'] =

    $form['title'] = [
      '#type' => 'item',
      '#markup' =>isset($allLanguages[$id]) ? $allLanguages[$id] : $id,
    ];

    $form = parent::buildForm($form, $form_state);

    // We do not allow editing of source language which is entity's ID.
    $form['source'] = [
      '#type' => 'value',
      '#value' => $mapping->getSource(),
    ];

    return $form;
  }


}
