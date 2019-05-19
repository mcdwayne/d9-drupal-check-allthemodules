<?php

namespace Drupal\webform_quiz\Plugin\WebformElement;

use Drupal;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\Radios;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_quiz_radios' element.
 *
 * @WebformElement(
 *   id = "webform_quiz_radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Webform Quiz Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons with a correct answer provided."),
 *   category = @Translation("Webform Quiz"),
 * )
 */
class WebformQuizRadios extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
        // Form display.
        'correct_answer' => [],
        'correct_answer_description' => '',
      ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['options']['options']['#type'] = 'webform_quiz_webform_element_options';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $storage = $form_state->getStorage();
    $element_properties = $storage['element_properties'];

    // Modify the existing element description to distinguish it from the
    // correct answer description.
    $form['element_description']['description']['#title'] = $this->t('Element Description');

    // Add a WYSIWYG for the correct answer description.
    $form['element_description']['correct_answer_description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Correct Answer Description'),
      '#description' => $this->t('A description of why the correct answer is correct.'),
      '#default_value' => isset($element_properties['correct_answer_description']) ? $element_properties['correct_answer_description'] : '',
      '#weight' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Make sure no blank options get submitted. If they are, just remove them.
    $values = $form_state->getValues();
    foreach ($values['options'] as $key => $value) {
      if (empty($value)) {
        unset($values['options'][$value]);
      }
    }
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // This addresses an issue where the webform_quiz_radios element was not
    // appearing in the webform.
    $element['#type'] = 'radios';

    $correct_answer_description_wrapper = [
      '#type' => 'container',
      '#attributes' => ['id' => 'correct-answer-description-wrapper'],
    ];
    $element['#suffix'] = Drupal::service('renderer')->render($correct_answer_description_wrapper);

    if (!empty($element['#correct_answer_description'])) {
      $element['#ajax'] = [
        'callback' => 'Drupal\webform_quiz\Plugin\WebformElement\WebformQuizRadios::ajaxShowCorrectAnswerDescription',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ];
    }

    parent::prepare($element, $webform_submission);
  }

  /**
   * Ajax handler to help show the correct description when user clicks an
   * option.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public static function ajaxShowCorrectAnswerDescription(&$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $triggering_element = $form_state->getTriggeringElement();
    $element_key = $triggering_element['#name'];

    /** @var \Drupal\webform\WebformSubmissionForm $form_obj */
    $form_obj = $form_state->getFormObject();
    $webform = $form_obj->getWebform();
    $element = $webform->getElement($element_key);
    $description = isset($element['#correct_answer_description']) ? $element['#correct_answer_description'] : '';

    $build['#type'] = 'container';
    $build['#attributes']['id'] = 'correct-answer-description-wrapper';
    $build['description'] = [
      '#type' => 'webform_quiz_correct_answer_description',
      '#correct_answer' => $element['#correct_answer'],
      '#correct_answer_description' => $description,
      '#triggering_element' => $triggering_element,
    ];

    $ajax_response->addCommand(new HtmlCommand('#correct-answer-description-wrapper', $build));

    // Allow other modules to add ajax commands.
    Drupal::moduleHandler()->invokeAll('webform_quiz_correct_answer_shown', [$ajax_response, $element, $form_state]);

    return $ajax_response;
  }

}
