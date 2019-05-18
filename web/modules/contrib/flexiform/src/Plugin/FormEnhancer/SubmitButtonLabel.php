<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Drupal\flexiform\FormEnhancer\SubmitButtonFormEnhancerTrait;

/**
 * FormEnhancer for altering the labels of submit buttons.
 *
 * @FormEnhancer(
 *   id = "submit_button_label",
 *   label = @Translation("Button Labels"),
 * );
 */
class SubmitButtonLabel extends ConfigurableFormEnhancerBase {
  use SubmitButtonFormEnhancerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'process_form',
  ];

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->locateSubmitButtons() as $path => $label) {
      $original_path = $path;
      $path = str_replace('][', '::', $path);
      $form['label'][$path] = [
        '#type' => 'textfield',
        '#title' => $this->t('@label Button Text', ['@label' => $label]),
        '#description' => 'Array Parents: ' . $original_path,
        '#default_value' => !empty($this->configuration[$path]) ? $this->configuration[$path] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValue($form['#parents']);
  }

  /**
   * Process Form Enhancer.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    foreach (array_filter($this->configuration) as $key => $label) {
      $array_parents = explode('::', $key);
      $button = [];
      $button = NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        $button['#value'] = $label;
        NestedArray::setValue($element, $array_parents, $button);
      }
    }
    return $element;
  }

}
