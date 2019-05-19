<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\field\XmlFieldHelperTrait.
 */

namespace Drupal\views_xml_backend\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * A handler to provide an XML text field.
 */
trait XmlFieldHelperTrait {

  /**
   * Provides the handler some groupby.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->field_alias = $this->options['id'];
  }

  /**
   * Returns the default options for XML fields.
   *
   * @return array
   *   The default options array.
   */
  protected function getDefaultXmlOptions() {
    $options = [];

    $options['xpath_selector']['default'] = '';
    $options['type']['default'] = 'separator';
    $options['separator']['default'] = ', ';

    return $options;
  }

  /**
   * Returns the default options form for XML fields.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form array.
   */
  public function getDefaultXmlOptionsForm(array $form, FormStateInterface $form_state) {
    $form['xpath_selector'] = [
      '#title' => $this->t('XPath selector'),
      '#description' => $this->t('The xpath selector'),
      '#type' => 'textfield',
      '#default_value' => $this->options['xpath_selector'],
      '#required' => TRUE,
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#options' => [
        'ul' => $this->t('Unordered list'),
        'ol' => $this->t('Ordered list'),
        'separator' => $this->t('Simple separator'),
      ],
      '#default_value' => $this->options['type'],
    ];

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $this->options['separator'],
      '#states' => [
        'visible' => [
          ':input[name="options[type]"]' => ['value' => 'separator'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Render all items in this field together.
   *
   * @param array $items
   *   The items provided by getItems for a single row.
   *
   * @return string
   *   The rendered items.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function renderItems($items) {
    if (!empty($items)) {
      if ($this->options['type'] == 'separator') {
        $render = [
          '#type' => 'inline_template',
          '#template' => '{{ items|safe_join(separator) }}',
          '#context' => [
            'items' => $items,
            'separator' => $this->sanitizeValue($this->options['separator'], 'xss_admin'),
          ],
        ];
      }
      else {
        $render = array(
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => $this->options['type'],
        );
      }
      return drupal_render($render);
    }
  }

  /**
   * Gets an array of items for the field.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row object containing the values.
   *
   * @return array
   *   An array of items for the field.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function getItems(ResultRow $row) {
    $return = [];
    if ($values = $this->getValue($row)) {
      foreach ($values as $value) {
        $return[] = ['value' => $value];
      }
    }

    return $return;
  }

  /**
   * Renders a single item of a row.
   *
   * @param int $count
   *   The index of the item inside the row.
   * @param mixed $item
   *   The item for the field to render.
   *
   * @return string
   *   The rendered output.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function render_item($count, $item) {
    return $item['value'];
  }

}
