<?php

namespace Drupal\webform_quiz\EntitySettings;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformEntityAddForm;

/**
 * Form to add a webform entity that is a quiz.
 */
class WebformQuizAddForm extends WebformEntityAddForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $this->getEntity();

    $quiz_settings = [
      'is_this_a_quiz' => '1',
    ];
    $webform->setThirdPartySetting('webform_quiz', 'settings', $quiz_settings);

    parent::submitForm($form, $form_state);
  }

}
