<?php

/**
 * @file
 * Contains Drupal\field_quiz\Plugin\Field\FieldFormatter\QuestionTextFormatter.
 */

namespace Drupal\field_quiz\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_quiz\Form\FieldQuizAnswerForm;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "field_quiz_question_formatter",
 *   module = "field_quiz",
 *   label = @Translation("Field Quiz Question Formatter"),
 *   field_types = {
 *     "field_quiz_question"
 *   }
 * )
 */
class QuestionTextFormatter extends FormatterBase {

    /**
    * {@inheritdoc}
    */
    public function viewElements(FieldItemListInterface $items, $langcode) {

        // Get the current node.
        $node = \Drupal::routeMatch()->getParameter('node');

        // Build the field quiz answer form
        $form_object = new FieldQuizAnswerForm($node, $items);
        $form = \Drupal::formBuilder()->getForm($form_object);

        $elements = array();

        // Add the output to the node.
        $elements[] = $build['field_quiz'] = [
        '#theme' => 'field_quiz_question_view',
        '#form' => $form,
        ];

        return $elements;
    }

}
