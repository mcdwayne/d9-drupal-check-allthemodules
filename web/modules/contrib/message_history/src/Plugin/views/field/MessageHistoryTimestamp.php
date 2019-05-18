<?php

namespace Drupal\message_history\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display the marker for new messages.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("message_history_timestamp")
 */
class MessageHistoryTimestamp extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!\Drupal::currentUser()->isAuthenticated()) {
      return;
    }
    $this->additional_fields['created'] = [
      'table' => 'message_field_data',
      'field' => 'created',
    ];
    // Don't add the additional fields to groupby.
    if (!empty($this->options['link_to_message'])) {
      $this->additional_fields['mid'] = ['table' => 'message_field_data', 'field' => 'mid'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_message'] = ['default' => isset($this->definition['link_to_message default']) ? $this->definition['link_to_message default'] : FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_message'] = [
      '#title' => $this->t('Link this field to the original piece of content'),
      '#description' => $this->t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_message']),
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Only add ourselves to the query if logged in.
    if (\Drupal::currentUser()->isAnonymous()) {
      return;
    }
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $mark = MARK_READ;
    if (!\Drupal::currentUser()->isAuthenticated()) {
      return;
    }

    $last_read = $this->getValue($values);
    $created = $this->getValue($values, 'created');

    if (!$last_read && $created > HISTORY_READ_LIMIT) {
      $mark = MARK_NEW;
    }
    elseif ($created > $last_read && $created > HISTORY_READ_LIMIT) {
      $mark = MARK_UPDATED;
    }
    $build = [
      '#theme' => 'mark',
      '#status' => $mark,
    ];
    return $this->renderLink(drupal_render($build), $values);
  }

  /**
   * Prepares link to the message.
   *
   * @param string $data
   *   The XSS safe string for the link text.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from a single row of a view's query result.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    if (empty($this->options['link_to_message']) || empty($this->additional_fields['mid'])) {
      return $data;
    }
    if ($data === NULL || $data === '') {
      $this->options['alter']['make_link'] = FALSE;
    }
    else {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['url'] = Url::fromRoute('entity.message.canonical', ['message' => $this->getValue($values, 'mid')]);
    }
    return $data;
  }

}
