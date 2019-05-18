<?php
/**
 * Created by PhpStorm.
 * User: evolve
 * Date: 9/26/17
 * Time: 6:42 PM
 */

namespace Drupal\persian_date\Element;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Date;
use Drupal\persian_date\Library\Jalali\jDate;

/**
 * Class PersianDate
 * @package Drupal\persian_date\Element
 * @FormElement("date")
 */
class PersianDate extends Date
{
    public function getInfo()
    {
        $info = parent::getInfo();
        $info['#attributes'] = ['type' => 'text'];
        return $info;
    }

    public static function processDate(&$element, FormStateInterface $form_state, &$complete_form)
    {
        // Attach JS support for the date field, if we can determine which date
        // format should be used.
        if ($element['#attributes']['type'] == 'date' && !empty($element['#date_date_format'])) {
            $element['#attributes']['type'] = 'text';
            // filter element goes to view
            if ($element['#date_date_format'] === 'Y-m-d' && $element['#value']) {
                list($year,,) = explode('-',$element['#value']);
                if (is_georgian_year($year)) {
                    $element['#value'] = jDate::forge($element['#value'])->format('Y-m-d');
                }
            }
            $element['#attached']['library'][] = 'persian_date/core';
            $element['#attributes']['data-drupal-date-format'] = [$element['#date_date_format']];
        }
        return $element;
    }
}
