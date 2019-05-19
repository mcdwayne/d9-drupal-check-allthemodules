<?php

namespace Drupal\webform_quiz\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display webform descriptions.
 *
 * @RenderElement("webform_quiz_correct_answer_description")
 */
class WebformQuizCorrectAnswerDescription extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#theme' => 'webform_quiz_correct_answer_description',
      '#correct_answer' => [],
      '#correct_answer_description' => NULL,
      '#triggering_element' => [],
      '#pre_render' => [
        [$class, 'preRenderWebformQuizCorrectAnswerDescription'],
      ],
    ];
  }

  /**
   * Create a display for the webform correct answer description.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with webform submission information.
   */
  public static function preRenderWebformQuizCorrectAnswerDescription(array $element) {
    $triggering_element = $element['#triggering_element'];
    $correct_answers = $element['#correct_answer'];
    $user_selected_value = $triggering_element['#default_value'];

    $element['#is_user_correct'] = in_array($user_selected_value, $correct_answers);

    return $element;
  }

}
