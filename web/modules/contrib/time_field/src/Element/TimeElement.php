<?php

namespace Drupal\time_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\time_field\Time;

/**
 * Provides a time field form element.
 *
 * Usage example:
 *
 * @code
 * $form['time'] = array(
 *   '#type' => 'time',
 *   '#title' => $this->t('Time'),
 * '#required' => TRUE,
 * );
 * @endcode
 *
 * @FormElement("time")
 */
class TimeElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#show_seconds' => false,
      '#input' => TRUE,
      '#process' => [
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderTime'],
      ],
      '#theme' => 'input__time',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE && !empty($element['#default_value'])) {
      $input = $element['#default_value'];
    }

    if (!empty($input)) {
      $time = Time::createFromHtml5Format($input);
      return $time->getTimestamp();
    }

    return NULL;
  }

  /**
   * Prepares a #type 'time' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   *
   * @see \Drupal\time_field\Plugin\Field\FieldWidget\TimeWidget::formElement()
   */
  public static function preRenderTime(array $element) {
    $element['#attributes']['type'] = 'time';
    $element['#attributes']['class'] = ['form-time'];

    // In ajax request value is set to raw timestamp
    // perform a better solution here.
    $isValuePassedInTimestampFormat = preg_match('/^\d+$/', $element['#value']);
    if ($isValuePassedInTimestampFormat) {
      $element['#value'] = Time::createFromTimestamp($element['#value'])
        ->formatForWidget($element['#show_seconds']);
    }

    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
      'placeholder',
    ]);
    static::setAttributes($element, ['form-text']);

    return $element;
  }

}
