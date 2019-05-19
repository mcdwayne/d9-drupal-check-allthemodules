<?php

namespace Drupal\webform_quiz\Plugin\WebformQuizSubmitHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform_quiz\Plugin\WebformQuizSubmitHandlerInterface;
use Drupal\webform_ui\Form\WebformUiElementFormBase;
use Exception;


/**
 * @WebformQuizSubmitHandler(
 *  id = "webform_quiz_element_config_submit",
 *  label = @Translation("Webform Quiz Element Config Submit."),
 * )
 */
class WebformQuizElementConfigSubmit extends PluginBase implements WebformQuizSubmitHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function handleSubmit(&$form, FormStateInterface $form_state) {
    $parent_key = $form_state->getValue('parent_key');
    $key = $form_state->getValue('key');

    $form_obj = $form_state->getFormObject();

    if ($form_obj instanceof WebformUiElementFormBase) {
      $element_plugin = $form_obj->getWebformElementPlugin();
      $webform = $form_obj->getWebform();

      // Submit element configuration.
      // Generally, elements will not be processing any submitted properties.
      // It is possible that a custom element might need to call a third-party API
      // to 'register' the element.
      $subform_state = SubformState::createForSubform($form['properties'], $form, $form_state);
      $element_plugin->submitConfigurationForm($form, $subform_state);

      // Add/update the element to the webform.
      $properties = $element_plugin->getConfigurationFormProperties($form, $subform_state);

      // Store the correct answer based on what the user checked.
      // This will be an array.
      $user_input = $form_state->getUserInput();
      $items = $user_input['properties']['options']['custom']['options']['items'];
      $correct_answers = [];
      foreach ($items as $item) {
        if (!empty($item['is_correct_answer'])) {
          $correct_answers[$item['value']] = $item['value'];
        }
      }

      if (empty($key)) {
        // todo: create a more specific exception.
        $message = 'The configuration cannot be saved because the webform element key cannot be empty.';
        throw new Exception($message);
      }

      // Save the correct answer description.
      $values = $form_state->getValues();
      $correct_answer_description = $values['correct_answer_description'];

      $properties['#correct_answer'] = $correct_answers;
      $properties['#correct_answer_description'] = $correct_answer_description;

      $webform->setElementProperties($key, $properties, $parent_key);

      // Save the webform.
      $webform->save();
    }
  }

}
