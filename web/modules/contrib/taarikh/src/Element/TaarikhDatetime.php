<?php

namespace Drupal\taarikh\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a datetime element.
 *
 * @FormElement("taarikh_datetime")
 */
class TaarikhDatetime extends Datetime {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
//    $info['#date_year_range'] = '1300:1450';
    $info['#date_first_day'] = 0;

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processDatetime($element, $form_state, $complete_form);

    // We should use our form element instead of the default date.
    if (!empty($element['date'])) {
      $element['date']['#type'] = 'taarikh_date';
      $element['date']['#taarikh_algorithm'] = !empty($element['#taarikh_algorithm']) ? $element['#taarikh_algorithm'] : 'fatimid_astronomical';
//      $element['date']['#date_year_range'] = !empty($element['#date_year_range']) ? $element['#date_year_range'] : '1300:1450';
      $element['date']['#date_first_day'] = !empty($element['#date_first_day']) ? $element['#date_first_day'] : 0;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateDatetime(&$element, FormStateInterface $form_state, &$complete_form) {
    // Before calling the Datetime's validateDatetime, override the
    // 'object' key in formstate with updated date from taarikh_date
    // element.
    // This does not happen by default since the Datetime's implementation
    // of validateDatetime relies only on 'object'. It is not a good idea
    // to set this key in taarikh_date itself as it may be used
    // independently of a Datetime (taarikh_datetime) widget as well.
    $parents = $element['#parents'];
    $input_exists = FALSE;
    $datetime = NestedArray::getValue($form_state->getValues(), $parents, $input_exists);

    if ($input_exists) {
      $title = !empty($element['#title']) ? $element['#title'] : '';
      try {
        // Parse the date string into an object.
        $format = DateFormat::load('html_date')->getPattern();
        $date = DrupalDateTime::createFromFormat($format, $datetime['date'], NULL);

        // We could even set the object reference (which means we don't even
        // need form_state->setValue, but let's be clean and clone the thing.
        $object = clone $datetime['object'];
        $object->setDate(
          (int) $date->format('Y'),
          (int) $date->format('n'),
          (int) $date->format('j')
        );

        $parents[] = 'object';
        $form_state->setValue($parents, $object);
      }
      catch (\Exception $ex) {
        $form_state->setError($element, t('The %field date could not be parsed.', ['%field' => $title]));
      }

      parent::validateDatetime($element, $form_state, $complete_form);
    }
  }

}
