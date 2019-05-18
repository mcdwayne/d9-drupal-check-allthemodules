<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FlagMappingAddForm.
 *
 * Provides the add form for our FlagMapping entity.
 *
 * @package Drupal\flags_languages\Form
 *
 * @ingroup flags_languages
 */
class LanguageMappingAddForm extends LanguageConfigEntityFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For our add form, we only need to change the text of the submit button.
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
    $actions['submit']['#value'] = $this->t('Create mapping');
    return $actions;
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check if config entity that's being created already exists.
    $id = 'flags.language_flag_mapping.' . $form_state->getValue('source');

    if (!$this->config($id)->isNew()) {
      $form_state->setErrorByName(
        'source',
        $this->t('Mapping for this language already exists.')
      );
    }
  }


}
