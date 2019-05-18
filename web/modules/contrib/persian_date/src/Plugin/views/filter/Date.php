<?php

namespace Drupal\persian_date\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\persian_date\Library\Jalali\jDateTime;
use Drupal\persian_date\Service\Translation\OffsetFilterTranslator;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends \Drupal\views\Plugin\views\filter\Date
{

  /**
   * @inheritDoc
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (isset($form['value'])) {
      $form['value']['#attributes']['autocomplete'] = 'off';
    }
  }

  protected function opSimple($field)
    {
        // if type is offset translate value and delegate handling to parent class
        if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
            $this->value['value'] = OffsetFilterTranslator::translate($this->value['value']);
            parent::opSimple($field);
            return;
        }

      $min = $value = (jDateTime::createCarbonFromFormat('Y-m-d H:i:s', $this->value['value'] . ' 00:00:00'));
      $max = $value = (jDateTime::createCarbonFromFormat('Y-m-d H:i:s', $this->value['value'] . ' 23:59:59'));
      $a = intval($min->getTimestamp());
      $b = intval($max->getTimestamp());

      // This is safe because we are manually scrubbing the values.
      // It is necessary to do it this way because $a and $b are formulas when using an offset.
      $this->query->addWhereExpression($this->options['group'], "$field BETWEEN $a AND $b");
    }

    protected function opBetween($field)
    {
        // if type is offset translate value and delegate handling to parent class
        if ($this->value['type'] == 'offset') {
            $this->value['min'] = OffsetFilterTranslator::translate($this->value['min']);
            $this->value['max'] = OffsetFilterTranslator::translate($this->value['max']);
            parent::opBetween($field);
            return;
        }

        $min = $value = (jDateTime::createCarbonFromFormat('Y-m-d H:i:s', $this->value['min'] . ' 00:00:00'));
        $max = $value = (jDateTime::createCarbonFromFormat('Y-m-d H:i:s', $this->value['max'] . ' 23:59:59'));
        $a = intval($min->getTimestamp());
        $b = intval($max->getTimestamp());

        // This is safe because we are manually scrubbing the values.
        // It is necessary to do it this way because $a and $b are formulas when using an offset.
        $operator = strtoupper($this->operator);
        $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
    }
}
