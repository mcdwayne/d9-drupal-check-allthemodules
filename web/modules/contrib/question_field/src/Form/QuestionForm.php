<?php

namespace Drupal\question_field\Form;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\question_field\AnswerStorage;
use Drupal\question_field\Plugin\Field\FieldType\QuestionItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuestionForm.
 */
class QuestionForm extends FormBase {

  /**
   * The answer storage.
   *
   * @var \Drupal\question_field\AnswerStorage
   */
  protected $storage;

  /**
   * The field items/ the questions.
   *
   * @var \Drupal\Core\Field\FieldItemList
   */
  protected $items;

  /**
   * Constructs a new QuestionForm.
   *
   * @param \Drupal\question_field\AnswerStorage $storage
   *   The answer storage.
   */
  public function __construct(AnswerStorage $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('question_field.storage')
    );
  }

  /**
   * Set the items.
   *
   * @param \Drupal\Core\Field\FieldItemList $items
   *   The items.
   */
  public function setItems(FieldItemList $items) {
    $this->items = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'question_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->items) {
      throw \Exception('Attach items before building the form.');
    }

    // Get the entity and field name keys.
    $values = $this->storage->getItemValues($this->items);

    // Add all of the questions.
    /** @var \Drupal\question_field\AnswerOptions[] $item_answer_options */
    $item_answer_options = [];
    foreach ($this->items as $delta => $item) {
      /** @var \Drupal\question_field\Plugin\Field\FieldType\QuestionItem $item */

      // Create the base element.
      $element = [
        '#title' => $item->getQuestion(),
        '#type' => $item->getAnswerType(),
        '#default_value' => isset($values[$delta]) ? $values[$delta] : $item->getAnswerDefault(),
      ];
      if ($item->getAnswerRequired()) {
        $element['#required'] = TRUE;
      }
      $description = $item->getQuestionDescription();
      if ($description) {
        $element['#description'] = $description;
      }

      // Add the options to the element.
      $item_answer_options[$delta] = $item->getAnswerOptions();
      $options = [];
      foreach ($item_answer_options[$delta] as $option) {
        /** @var \Drupal\question_field\AnswerOptions $option */
        $options[$option->getValue()] = $option->getText();
      }
      if ($options) {
        $element['#options'] = $options;
      }

      // Add the element to the form.
      $form['question_field']['question_field_' . $delta] = $element;
    }

    // Now add the states that tie the follow-up questions together.
    foreach ($item_answer_options as $delta => $answer_options) {
      // If there are questions with follow-up questions, set the display states.
      foreach ($answer_options as $option) {
        $followups = $option->getFollowups();
        if ($followups) {
          $name = 'question_field_' . $delta;
          $selector = ':input[name="' . $name . '"]';
          foreach ($followups as $followup) {
            $form['question_field']['question_field_' . $followup]['#states'] = [
              'visible' => [$selector => ['value' => $option->getValue()]],
            ];
          }
        }
      }
    }

    // Add a submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit answers'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the values to save.
    // @todo: Why aren't values set, using user input is dangerous.
    $user_input = $form_state->getUserInput();
    $values = [];
    foreach ($this->items as $delta => $item) {
      $values[$delta] = $user_input['question_field_' . $delta];
    }

    // Save the values.
    $this->storage->setItemValues($this->items, $values);
  }

  /**
   * Delete the field values.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function deleteForm(array &$form, FormStateInterface $form_state) {
    $this->storage->deleteItemValues($this->items);
  }

}
