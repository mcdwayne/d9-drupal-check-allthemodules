<?php

namespace Drupal\eqsf\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'eqsf_widget' widget.
 *
 * @FieldWidget(
 *   id = "eqsf_widget",
 *   label = @Translation("Entityqueue Scheduler"),
 *   field_types = {
 *     "eqsf_field"
 *   }
 * )
 */
class EqsfWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $widget = array();
    $select = isset($items[$delta]->select) ? $items[$delta]->select : 'empty';
    $position = isset($items[$delta]->position) ? $items[$delta]->position : '300';
    $entity_position = ['300' => t('Bottom'), '-300' => t('Top')];

    if (!empty($items[$delta]->startdate)) {
      $startdate = isset($items[$delta]->startdate) ? date('Y-m-d H:i', $items[$delta]->startdate) : date('Y-m-d H:i', REQUEST_TIME);
      $startdate = new DrupalDateTime($startdate);
    }
    else {
      $startdate = '';
    }

    if (!empty($items[$delta]->enddate)) {
      $enddate = isset($items[$delta]->enddate) ? date('Y-m-d H:i', $items[$delta]->enddate) : '';
      $enddate = new DrupalDateTime($enddate);
    }
    else {
      $enddate = '';
    }

    //var_dump(new DrupalDateTime($startdate));
    //var_dump(new DrupalDateTime($enddate));

    $class = get_class($this);

    $query = \Drupal::database()->select('entity_subqueue', 'esq');
    $query->fields('esq', array('title', 'queue'));
    $result = $query->execute()->fetchAll();
    $entity_subqueue['empty'] = t("No entity queue selected");
    foreach ($result as $queue) {
      $entity_subqueue[$queue->queue] = $queue->title;
    }

    $widget['select'] = array(
      '#type'          => 'select',
      '#title'         => t('Choose your entity queue'),
      '#default_value' => $select,
      '#options'       => $entity_subqueue,
      '#attributes'    => array('class' => array('eqsf-selector')),
      '#attached'      => array(
        'library' => array(
          'eqsf/eqsf.field',
        ),
      ),
    );

    $widget['startdate'] = array(
      '#type'             => 'datetime',
      '#element_validate' => array(
        array($class, 'validateDatetime'),
      ),
      '#title'            => t('Start date/time of the publishing.'),
      '#default_value'    => new DrupalDateTime($startdate),
      '#prefix'           => '<div class="eqsf-field-wrapper">',
      '#suffix'           => '</div>',
      '#attributes'       => array(
        'class' => array(
          'eqsf-element',
          'timepicker',
          EQSF_TYPE_START,
        ),
      ),
    );

    $widget['enddate'] = array(
      '#type'             => 'datetime',
      '#element_validate' => array(
        array($class, 'validateDatetime'),
      ),
      '#title'            => t('End date/time of the publishing.'),
      '#default_value'    => new DrupalDateTime($enddate),
      '#prefix'           => '<div class="eqsf-field-wrapper">',
      '#suffix'           => '</div>',
      '#attributes'       => array(
        'class' => array(
          'eqsf-element',
          'timepicker',
          EQSF_TYPE_END,
        ),
      ),
    );

    $widget['position'] = array(
      '#type'          => 'select',
      '#title'         => t('Choose queue position'),
      '#default_value' => $position,
      '#options'       => $entity_position,
      '#prefix'        => '<div class="eqsf-field-wrapper">',
      '#suffix'        => '</div>',
      '#attributes'    => array('class' => array('eqsf-selector')),
    );

    return $widget;
  }

  /**
   * Validation callback for a datetime element.
   *
   * If the date is valid, the date object created from the user input is set in
   * the form for use by the caller. The work of compiling the user input back
   * into a date object is handled by the value callback, so we can use it here.
   * We also have the raw input available for validation testing.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $input_exists = FALSE;
    $input = NestedArray::getValue($form_state->getValues(), $element['#parents'], $input_exists);
    if ($input_exists) {
      $title = !empty($element['#title']) ? $element['#title'] : '';
      $date_format = $element['#date_date_element'] != 'none' ? static::getHtml5DateFormat($element) : '';
      $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
      $format = trim($date_format . ' ' . $time_format);

      // If there's empty input and the field is not required, set it to empty.
      if (empty($input['date']) && empty($input['time']) && !$element['#required']) {
        $form_state->setValueForElement($element, NULL);
      }
      // If there's empty input and the field is required, set an error. A
      // reminder of the required format in the message provides a good UX.
      elseif (empty($input['date']) && empty($input['time']) && $element['#required']) {
        $form_state->setError($element, t('The %field date is required. Please enter a date in the format %format.', array(
          '%field'  => $title,
          '%format' => static::formatExample($format)
        )));
      }
      else {
        // If the date is valid, set it.
        $date = $input['object'];
        if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
          $form_state->setValueForElement($element, $date);
        }
        // If the date is invalid, set an error. A reminder of the required
        // format in the message provides a good UX.
        else {
          $form_state->setError($element, t('The %field date is invalid. Please enter a date in the format %format.', array(
            '%field'  => $title,
            '%format' => static::formatExample($format)
          )));
        }
      }
    }
  }

  /**
   * Retrieves the right format for a HTML5 date element.
   *
   * The format is important because these elements will not work with any other
   * format.
   *
   * @param string $element
   *   The $element to assess.
   *
   * @return string
   *   Returns the right format for the date element, or the original format
   *   if this is not a HTML5 element.
   */
  protected static function getHtml5DateFormat($element) {
    switch ($element['#date_date_element']) {
      case 'date':
        return DateFormat::load('html_date')->getPattern();

      case 'datetime':
      case 'datetime-local':
        return DateFormat::load('html_datetime')->getPattern();

      default:
        return $element['#date_date_format'];
    }
  }

  /**
   * Retrieves the right format for a HTML5 time element.
   *
   * The format is important because these elements will not work with any other
   * format.
   *
   * @param string $element
   *   The $element to assess.
   *
   * @return string
   *   Returns the right format for the time element, or the original format
   *   if this is not a HTML5 element.
   */
  protected static function getHtml5TimeFormat($element) {
    switch ($element['#date_time_element']) {
      case 'time':
        return DateFormat::load('html_time')->getPattern();

      default:
        return $element['#date_time_format'];
    }
  }
}


