<?php

/**
 * Contains \Drupal\riddler\Form\RiddlerSettingsForm.
 */

namespace Drupal\riddler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Displays the Riddler settings form.
 */
class RiddlerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'riddler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['riddler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // This can be rebuilt by ajax, so check for existing values. in $form_state.
    // Run through array values to reset keys in case one was ajax removed.
    $values = $form_state->getValues();
    $riddles = isset($values['riddler']) ? $values['riddler'] : [];

    // Without checking if there was a triggering element, this would reload
    // the original active config instead of deleting the last riddle. The
    // triggering element would be the remove or add another buttons.
    if (empty($riddles) && empty($form_state->getTriggeringElement())) {
      $riddles = $this->config('riddler.settings')->get('riddles');
    }

    // Add CSS for styling settings form.
    $form['#attached']['library'][] = 'riddler/base';

    // Initialize the counter if it hasn't been set. We do it this way so the
    // add more ajax callback can increment the max to create an empty riddle.
    $rebuild_info = $form_state->getRebuildInfo();
    if (!isset($rebuild_info['riddler']['items_count'])) {
      // Nested to avoid unlikely conflicts with other modules.
      $form_state->setRebuildInfo([
        'riddler' => [
          'items_count' => count($riddles) === 0 ? 1 : count($riddles),
        ]
      ]);
    }

    $max = $form_state->getRebuildInfo()['riddler']['items_count'];
    $form['riddler'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="riddler">',
      '#suffix' => '</div>'
    ];
    $form['riddler']['help'] = [
      '#markup' => $this->t('Add questions that you require users to answer. A random question will be presented to the user on forms as configured in the CAPTCHA settings. Allow more than one correct answer by entering a comma-separated list.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>'
    ];

    // Build or rebuild the riddle fields.
    for ($delta = 0; $delta < $max; $delta++) {
      if (!isset($form['riddler'][$delta])) {
        $element = [
          'question' => [
            '#type' => 'textfield',
            '#title' => 'Riddle ' . ($delta + 1) . ' question',
            '#default_value' => isset($riddles[$delta]) ? $riddles[$delta]['question'] : '',
            '#prefix' => '<div id="row-riddle' . ($delta + 1) . '" class="row-riddle">',
          ],
          'response' => [
            '#type' => 'textfield',
            '#title' => 'Answer',
            '#default_value' => isset($riddles[$delta]) ? $riddles[$delta]['response'] : '',
          ],

          // Delete a row button.
          'delete' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#id' => 'riddle' . $delta,
            '#name' => 'riddle-remove-' . $delta,
            '#submit' => [[$this, 'removeSubmit']],
            '#limit_validation_errors' => [[]],
            '#ajax' => [
              'callback' => [$this, 'removeCallback'],
              'wrapper' => 'riddler',
              'method' => 'replace',
              'effect' => 'fade',
            ],
            '#suffix' => '</div>',
          ],
        ];
        $form['riddler'][$delta] = $element;
      }
    }

    // Add a row button.
    $form['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another riddle'),
      '#submit' => [[$this, 'addMoreSubmit']],
      '#limit_validation_errors' => [['riddler']],
      '#ajax' => [
        'callback' => [$this, 'addMoreCallback'],
        'wrapper' => 'riddler',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit function for ajax Add another riddle button.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addMoreSubmit(&$form, FormStateInterface &$form_state) {
    $max = count($form_state->getValues()['riddler']);
    $form_state->setRebuildInfo([
      'riddler' => [
        'items_count' => ++$max,
      ]
    ]);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit callback for Add another riddle button.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  public function addMoreCallback(&$form, FormStateInterface $form_state) {
    return $form['riddler'];
  }

  /**
   * Submit function for remove a riddle button.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function removeSubmit(&$form, FormStateInterface &$form_state) {
    $this->removeFormStateValue($form_state);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit callback for remove a riddle button.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  public function removeCallback(&$form, FormStateInterface $form_state) {
    return $form['riddler'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#value'] == 'Remove') {
      return;
    }
    $values = $form_state->getValues()['riddler'];
    foreach ($values as $key => $value) {
      if (empty($value['question']) && empty($value['response'])) {
        unset($form_state->getValues('riddler')['riddler'][$key]);
      }
      else {
        if (empty($value['question'])) {
          $form_state->setError($form['riddler'][$key]['question'], $this->t('Please add a question.'));
        }
        if (empty($value['response'])) {
          $form_state->setError($form['riddler'][$key]['response'], $this->t('Please add an answer.'));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $riddles = $form_state->getValues()['riddler'];
    $this->recursiveUnset($riddles, 'delete');
    $this->config('riddler.settings')
      ->set('riddles', $riddles)
      ->save();
    parent::SubmitForm($form, $form_state);
  }

  /**
   * Remove elements from an array by key.
   */
  private function recursiveUnset(&$array, $unwanted_key) {
    unset($array[$unwanted_key]);
    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->recursiveUnset($value, $unwanted_key);
      }
    }
  }

  /**
   * Remove the riddle row from $form_state->values and $form_state->input.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function removeFormStateValue(FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $row = str_replace('riddle', '', $trigger['#id']);
    $values = $form_state->getValues();
    unset($values['riddler'][$row]);
    $values['riddler'] = array_values($values['riddler']);
    $form_state->setValue('riddler', $values['riddler']);
    $form_state->setUserInput($values);
  }
}
