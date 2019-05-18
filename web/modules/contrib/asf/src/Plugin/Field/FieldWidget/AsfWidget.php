<?php

namespace Drupal\asf\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\FIeld\FieldFilteredMarkup;
use Drupal\Core\FIeld\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\asf\AsfSchema;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Entity\DateFormat;

/**
 * Plugin implementation of the 'AsfWidget' widget.
 *
 * @FieldWidget(
 *   id = "AsfWidget",
 *   module = "asf",
 *   label = @Translation("Asf publication settings"),
 *   field_types = {
 *     "asf"
 *   }
 * )
 */
class AsfWidget extends WidgetBase {



  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = array();

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => 'KOEKOEK',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ? : $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += array(
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      );

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, array($field_name)));
        //$wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = array(
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => array('class' => array('field-add-more-submit')),
          '#limit_validation_errors' => array(array_merge($parents, array($field_name))),
          '#submit' => array(array(get_class($this), 'addMoreSubmit')),
          '#ajax' => array(
            'callback' => array(get_class($this), 'addMoreAjax'),
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ),
        );
      }
    }

    return $elements;
  }
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {


    $field_settings = $this->getFieldSettings();
    $widget = array();
    $publication_type = isset($items[$delta]->publication_type) ? $items[$delta]->publication_type : ASF_TYPE_NOTHING;

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

    $options = array(
      ASF_TYPE_NOTHING => 'Do nothing.',
      ASF_TYPE_START => 'publish from a DATE.',
      ASF_TYPE_START_END => 'publish on DATE, unpublish on end DATE',
      ASF_TYPE_ITERATE => 'publish from start TIME to end TIME, repeat multiple days',
      ASF_TYPE_ITERATE_AMOUNT => 'publish from start TIME to end TIME, repeat x times',
    );
    $widget['publication_type'] = array(
      '#type' => 'select',
      '#title' => t('How do you want this entity to be published.'),
      '#default_value' => $publication_type,
      '#options' => $options,
      '#attributes' => array('class' => array('iteration_toggler')),
      '#attached' => array(
        'library' => array(
          'asf/asf.field',
          'datetimepicker_lib/jquery.datetimepicker.css',
          'datetimepicker_lib/jquery.datetimepicker.js',
        ),
      ),
    );

//    $widget['iteration_end'] = array(
//      '#type' => 'select',
//      '#title' => t('Type of iteration'),
//      '#default_value' => isset($items[$delta]->iteration_end) ? $items[$delta]->iteration_end : 0,
//      '#attributes' => array(
//        'class' => array(
//          'interation_end',
//          ASF_TYPE_ITERATE
//        )
//      ),
//      '#options' => array(
//        ASF_ITERATION_ENDDATE => t('Loop until end date'),
//        ASF_ITERATION_MAX => t('Loop until amount of iterations reached'),
//        ASF_ITERATION_FIRST => t('Loop until first of both reached'),
//        ASF_ITERATION_INFINITE => t('Loop until the end of times'),
//      ),
//    );
    $class = get_class($this);

    $widget['startdate'] = array(
      '#type' => 'datetime',
      '#element_validate' => array(
        array($class, 'validateDatetime'),
      ),
      '#title' => t('Start date/time of the publishing.'),
      //'#default_value' => $startdate,
      '#default_value' => new DrupalDateTime($startdate),
      //'#date_date_element' => 'datetime',
      //'#date_time_element' => 'none',
      //'#date_year_range' => '1902:2037',
      //'#theme_wrappers' => array('datetime_wrapper','asf_datetime_wrapper'),
      //'#element_validate' => array('_asf_field_validate_ISO_date'),
      '#prefix' => '<div class="asf-datetime-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array(
          'asf-element',
          'timepicker',
          ASF_TYPE_START,
          ASF_TYPE_START_END,
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
        ),
      ),

    );

    $widget['enddate'] = array(
      '#type' => 'datetime',
      '#element_validate' => array(
        array($class, 'validateDatetime'),
      ),
      '#title' => t('End date/time of the publishing.'),
      '#default_value' => new DrupalDateTime($enddate),
      //'#theme_wrappers' => array('datetime_wrapper','asf_datetime_wrapper'),
      '#prefix' => '<div class="asf-datetime-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array(
          'asf-element',
          'timepicker',
          ASF_TYPE_START_END,
          ASF_TYPE_ITERATE,
        ),
      ),
    );

    $start_time = isset($items[$delta]->start_time) ? DrupalDateTime::createFromTimestamp($items[$delta]->start_time) : '';
    $widget['start_time'] = array(
      '#type' => 'textfield',
      '#title' => t('Publish at'),
      '#description' => t('in 24 h format e.g: 22:34'),
      '#default_value' => $start_time,
      //'#default_value' => new DrupalDateTime($startdate),
      '#type' => 'datetime',
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      //'#theme_wrappers' => array('datetime_wrapper','asf_datetime_wrapper'),
      '#prefix' => '<div class="asf-datetime-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array(
          'asf-element',
          'timepicker_time',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
      '#size' => 6,
    );
    $end_time = isset($items[$delta]->end_time) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_time) : '';
    $widget['end_time'] = array(
      '#type' => 'textfield',
      '#title' => t('unpublish at'),
      '#description' => t('in 24 h format e.g: 22:34'),
      //'#default_value' => isset($items[$delta]->end_time) ? $items[$delta]->end_time : '',
      '#default_value' => $end_time,
      '#type' => 'datetime',
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      //'#theme_wrappers' => array('datetime_wrapper','asf_datetime_wrapper'),
      '#prefix' => '<div class="asf-datetime-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => array(
        'class' => array(
          'asf-element',
          'timepicker_time',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
      '#size' => 6,
    );
//    $widget['end_time']['#theme_wrappers'][] = 'datetime_wrapper';
//    $widget['end_time']['#theme_wrappers'][] = 'asf_datetime_wrapper';


    $widget['iteration_day'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration day'),
      '#default_value' => isset($items[$delta]->iteration_day) ? $items[$delta]->iteration_day : '*',
      '#size' => 5,
      //'#element_validate' => array('_asf_field_validate_iteration_integer_list'),
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
          'asf-clear',
        ),
      ),
    );
    $widget['iteration_week'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration week'),
      '#default_value' => isset($items[$delta]->iteration_week) ? $items[$delta]->iteration_week : '*',
      '#size' => 5,
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
    );
    $widget['iteration_weekday'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration weekday'),
      '#default_value' => isset($items[$delta]->iteration_weekday) ? $items[$delta]->iteration_weekday : '*',
      '#size' => 5,
      //'#element_validate' => array('_asf_field_validate_iteration_integer_list'),
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
    );

    $widget['iteration_month'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration month'),
      '#default_value' => isset($items[$delta]->iteration_month) ? $items[$delta]->iteration_month : '*',
      '#size' => 5,
      //'#element_validate' => array('_asf_field_validate_iteration_integer_list'),
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
    );

    $widget['iteration_year'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration year'),
      '#default_value' => isset($items[$delta]->iteration_year) ? $items[$delta]->iteration_year : '*',
      '#size' => 5,
      //'#element_validate' => array('_asf_field_validate_iteration_integer_list'),
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
    );

    $widget['iteration_max'] = array(
      '#type' => 'textfield',
      '#title' => t('Iteration max'),
      '#description' => t('The iteration will occurr this many times'),
      '#default_value' => isset($items[$delta]->iteration_max) ? $items[$delta]->iteration_max : '',
      '#size' => 5,
      '#attributes' => array(
        'class' => array(
          'asf-element',
          //ASF_TYPE_ITERATE,
          ASF_TYPE_ITERATE_AMOUNT,
          'asf-inline',
        ),
      ),
    );


    $widget['inherit_eid'] = array(
      '#type' => 'textfield',
      '#title' => t('inherit Eid'),
      '#default_value' => isset($items[$delta]->inherit_eid) ? $items[$delta]->inherit_eid : '',
      '#attributes' => array(
        'class' => array(
          'asf-element',
          ASF_TYPE_INHERIT,
          'asf-inline',
        )
      ),
    );

    return $widget;
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
//    if (strlen($value) == 0) {
//      $form_state->setValueForElement($element, '');
//      return;
//    }
//    if (!preg_match('/^#([a-f0-9]{6})$/iD', strtolower($value))) {
//      $form_state->setError($element, t("Color must be a 6-digit hexadecimal value, suitable for CSS."));
//    }
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
        $form_state->setError($element, t('The %field date is required. Please enter a date in the format %format.', array('%field' => $title, '%format' => static::formatExample($format))));
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
          $form_state->setError($element, t('The %field date is invalid. Please enter a date in the format %format.', array('%field' => $title, '%format' => static::formatExample($format))));
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
