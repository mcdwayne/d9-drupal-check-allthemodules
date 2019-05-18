<?php

namespace Drupal\cloudwords_translation\Form;

use Drupal\Core\Form\FormStateInterface;

class CloudwordsTranslationFormAlter {

  /**
   * Handles the cloudwords translation form submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function entityFormSubmit(array &$form, FormStateInterface $form_state) {
    // update cloudwords translatable translation status to out of date if user marks it in Drupal.
    $values = $form_state->getValue('content_translation', []);
    if(isset($values['retranslate']) && $values['retranslate'] == 1){
      $entity_id = $form_state->getFormObject()->getEntity()->id();

      $translation_module = 'content';
      $textgroup = 'node';

      $translatables = cloudwords_get_translatables_by_property([
        'textgroup' => $textgroup,
        'translation_module' => $translation_module,
        'objectid' => $entity_id,
      ], 'language');

      foreach ($translatables as $langcode => $translatable) {
        if($translatable->get('translation_status')->value == CLOUDWORDS_TRANSLATION_STATUS_TRANSLATION_EXISTS){
          $translatable->translation_status = CLOUDWORDS_TRANSLATION_STATUS_OUT_OF_DATE;
          $translatable->save();
        }
      }
    }
  }
}