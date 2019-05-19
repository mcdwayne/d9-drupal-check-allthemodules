<?php

namespace Drupal\workflow_participants\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Limit nodes on a view where the current user is a workflow participant.
 *
 * @ViewsFilter("participant_role_filter")
 */
class ParticipantRoleFilter extends FilterPluginBase {

  /**
   * Disable the possibility to force a single value.
   *
   * @var bool
   */
  protected $alwaysMultiple = FALSE;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $form['value'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Participants'),
      '#options' => [
        'author' => $this->t('Author'),
        'editor' => $this->t('Editor'),
        'reviewer' => $this->t('Reviewer'),
      ],
      '#default_value' => $this->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    // Unset options that were not selected in the config.
    foreach ($this->options['value'] as $id => $value) {
      if (empty($value)) {
        unset($form[$this->field]['#options'][$id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Current user is equal to'),
        'method' => 'equal',
      ],
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $summary = [];

    if (!empty($this->options['value']['author'])) {
      $summary[] = $this->t('Author');
    }
    if (!empty($this->options['value']['editor'])) {
      $summary[] = $this->t('Editor');
    }
    if (!empty($this->options['value']['reviewer'])) {
      $summary[] = $this->t('Reviewer');
    }

    return implode(', ', $summary);
  }

  /**
   * Build strings from the operators() for 'select' options.
   *
   * @param string $which
   *   Which value to display in options list.
   *
   * @return array
   *   Array of option config.
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Add conditions to query based on operator.
   */
  protected function equal() {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $participants = ($this->options['exposed']) ? $this->value : $this->options['value'];
    $add_condition = FALSE;

    $condition = new Condition('OR');
    if (in_array('author', $participants, TRUE)) {
      $condition->condition("node_field_data.uid", "***CURRENT_USER***", '=');
      $add_condition = TRUE;
    }

    if (in_array('reviewer', $participants, TRUE)) {
      $table = $query->ensureTable('workflow_participants__reviewers', $this->relationship);
      $condition->condition("{$table}.reviewers_target_id", "***CURRENT_USER***", '=');
      $add_condition = TRUE;
    }

    if (in_array('editor', $participants, TRUE)) {
      $table = $query->ensureTable('workflow_participants__editors', $this->relationship);
      $condition->condition("{$table}.editors_target_id", "***CURRENT_USER***", '=');
      $add_condition = TRUE;
    }

    if ($add_condition) {
      $query->addWhere($this->options['group'], $condition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }

}
