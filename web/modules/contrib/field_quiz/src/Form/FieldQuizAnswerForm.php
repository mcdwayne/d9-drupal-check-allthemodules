<?php
/**
 * @file
 * Contains \Drupal\field_quiz\Form\FieldQuizAnswerForm.
 */

namespace Drupal\field_quiz\Form;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Provides a webform client form.
 */
class FieldQuizAnswerForm extends FormBase {

  /**
   * The node the client form belongs to.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The field items/ the questions.
   *
   * @var \Drupal\Core\Field\FieldItemList
   */
  protected $items;


  /**
   * FieldQuizAnswerForm constructor.
   * @param \Drupal\node\NodeInterface $node
   *   The node the client form belongs to.
   * @param FieldItemList $items
   */
  public function __construct(NodeInterface $node, FieldItemList $items) {
    $this->node = $node;
    $this->items = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
      return 'field_quiz_answer_form';
  }


  /**
   * Sort an array by random (shuffle) but preserve the keys
   * @param \Drupal\Core\Field\FieldItemList $items
   * @return \Drupal\Core\Field\FieldItemList $items
   */
  private function shuffle_answers_assoc(FieldItemList $items) {

    if (!empty($items)) {
      foreach ($items as $delta => $item) {
        $sort_array[$delta] = $item;
      }
      unset ($items);
      if (!empty($sort_array)) {
        $new_sorted_array = array();
        $keys = array_keys($sort_array);
        if (!empty($keys)) {
          shuffle($keys);
          foreach($keys as $key) {
            $new_sorted_array[$key] = $sort_array[$key];
          }
        }
        if (!empty($new_sorted_array)) {
          foreach ($new_sorted_array as $delta => $item) {
            $items[$delta] = $item;
          }
        }
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $node = $this->node;
    $form['#node'] = $node;
    $items = $this->items;
    $form['#items'] = $items;

    if (!empty($items)) {
      $config = $this->config('field_quiz.settings');

      // Shuffle questions if set in the administration settings.
      if (!empty($config->get('field_quiz.shuffle_answers'))) {
        // sort the answers each time by random
        $items = $this->shuffle_answers_assoc($items);
      }

      $show_button = FALSE;
      foreach ($items as $delta => $item) {
        $values = $values = $item->getValue();
        if (!empty($values['answer'])) {
          $show_button = TRUE;
          // provide each answer together with the checkbox
          $form['answers']['item_' . $delta] = array(
            '#type' => 'checkbox',
            '#default_value' => 0,
            '#title' => $values['answer'],
          );
        }
      }

      if ($show_button) {
        $form['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Submit'),
        );
      }

    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_state_values = $form_state->cleanValues()->getValues();

    if (!empty($form_state_values)) {

      $error = FALSE;

      $items = $form['#items'];
      $node = $form['#node'];

      if (!empty($items)) {

        foreach ($items as $delta => $item) {

          $item_values = $item->getValue();

          // compare the submitted answers with the correct answers
          // all correct answers should be answered correct - there are no differences between correct and given answers allowed

          // if the answer is available
          if (isset($form_state_values['item_' . $delta])) {
              // if the answer is different with the correct one
              if ($form_state_values['item_' . $delta] != $item_values['correct']) {
                  $error = TRUE;
              }
          }
          else {
              // no answer available for this delta - but should be there - error in the system
              $error = TRUE;
          }

        }

      }

      // if user answered wrong
      if ($error) {
        // redirect the user to the sorry page
        $url = new Url('field_quiz.sorry', array('node' => $node->id()));
        $form_state->setRedirectUrl($url);
      }
      else {
        // redirect the user to the success page
        $url = new Url('field_quiz.success', array('node' => $node->id()));
        $form_state->setRedirectUrl($url);
      }
    }

  }

}