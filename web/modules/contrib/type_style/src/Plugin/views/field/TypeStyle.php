<?php

namespace Drupal\type_style\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to output and arbitrary type style.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("type_style")
 */
class TypeStyle extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type_style_name'] = ['default' => ''];

    return $options;
  }

  /**
   * Gets the style name for this field.
   *
   * @return string
   *   The style name.
   */
  protected function getStyleName() {
    if ($this->realField !== 'type_style') {
      $style_name = str_replace('type_style_', '', $this->realField);
    }
    else {
      $style_name = $this->options['type_style_name'];
    }
    return $style_name;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($this->realField === 'type_style') {
      $form['type_style_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Type Style name'),
        '#description' => $this->t('The Type Style name, i.e. "color" or "icon".'),
        '#default_value' => $this->options['type_style_name'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return type_style_get_style($this->getEntity($values), $this->getStyleName(), '');
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(ResultRow $row, $output) {
    parent::postRender($row, $output);

    $style_name = $this->getStyleName();
    $render = $this->render($row);
    $replacements = [
      'data-type-style-color-' . $style_name => 'style="color: ' . $render . '"',
      'data-type-style-background-color-' . $style_name => 'style="background-color: ' . $render . '"',
    ];
    if ($style_name === 'color') {
      $replacements['data-type-style-color'] = 'style="color: ' . $render . '"';
      $replacements['data-type-style-background-color'] = 'style="background-color: ' . $render . '"';
    }
    return $replacements;
  }

}
