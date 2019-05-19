<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\Date.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\Date as ViewsDate;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Xpath;

/**
 * Date XML argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_xml_backend_date")
 */
class Date extends ViewsDate implements XmlArgumentInterface {

  use AdminLabelTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['xpath_selector']['default'] = '';

    return $options;
  }

  /**
   * {@inheritdoc}
    */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['xpath_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XPath selector'),
      '#description' => $this->t('The field name in the table that will be used as the filter.'),
      '#default_value' => $this->options['xpath_selector'],
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultArgumentForm(&$form, FormStateInterface $form_state) {
    parent::defaultArgumentForm($form, $form_state);
    unset($form['default_argument_type']['#options']['node_changed']);
    unset($form['default_argument_type']['#options']['node_created']);
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->query->addArgument($this);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $xpath = $this->options['xpath_selector'];
    $format = Xpath::escapeXpathString($this->argFormat);
    $value = Xpath::escapeXpathString($this->getValue());

    return "php:functionString('views_xml_backend_format_value', $xpath, $format) = $value";
  }

}
