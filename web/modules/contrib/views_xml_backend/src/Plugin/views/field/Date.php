<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\field\Date.
 */

namespace Drupal\views_xml_backend\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\Date as ViewsDate;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Sorter\DateSorter;

/**
 * A handler to provide an XML date field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_xml_backend_date")
 */
class Date extends ViewsDate implements MultiItemsFieldHandlerInterface {

  use XmlFieldHelperTrait;
  use AdminLabelTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions() + $this->getDefaultXmlOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form = $this->getDefaultXmlOptionsForm($form, $form_state);

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    $tmp_row = new ResultRow();

    if (!is_numeric($item['value'])) {
      $tmp_row->{$this->field_alias} = strtotime($item['value']);
    }
    else {
      $tmp_row->{$this->field_alias} = $item['value'];
    }

    return parent::render($tmp_row);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->query->addSort(new DateSorter($this->field_alias, $order));
  }

}
