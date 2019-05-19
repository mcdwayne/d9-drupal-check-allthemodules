<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\field\Standard.
 */

namespace Drupal\views_xml_backend\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Sorter\StringSorter;

/**
 * A handler to provide an XML text field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_xml_backend_standard")
 */
class Standard extends FieldPluginBase implements MultiItemsFieldHandlerInterface {

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
  public function clickSort($order) {
    $this->query->addSort(new StringSorter($this->field_alias, $order));
  }

}
