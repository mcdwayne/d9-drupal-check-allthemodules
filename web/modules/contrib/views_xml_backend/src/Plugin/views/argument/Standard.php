<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\Standard.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Xpath;

/**
 * Default implementation of the base argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_xml_backend_standard")
 */
class Standard extends ArgumentPluginBase implements XmlArgumentInterface {

  use AdminLabelTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // @todo: Handle group_by argument.
    $this->query->addArgument($this);
  }

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
  public function __toString() {
    $xpath = $this->options['xpath_selector'];
    $value = Xpath::escapeXpathString($this->getValue());

    return "$xpath = $value";
  }

}
