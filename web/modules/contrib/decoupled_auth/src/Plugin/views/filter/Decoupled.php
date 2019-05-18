<?php

namespace Drupal\decoupled_auth\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler for whether user is decoupled.
 *
 * Filter handler label is 'Has web account', so therefore if the user IS
 * decoupled, it does NOT have a web account.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_decoupled")
 */
class Decoupled extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->value_value = $this->t('Has web account?');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // Provide value options for has web account, Yes or No.
    $this->valueOptions = [1 => $this->t('No'), 0 => $this->t('Yes')];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }
    if ($exposed = $form_state->get('exposed')) {
      // Exposed filter: use a select box to save space.
      $filter_form_type = 'select';
    }
    else {
      // Configuring a filter: use radios for clarity.
      $filter_form_type = 'radios';
    }

    $form['value'] = [
      '#type' => $filter_form_type,
      '#title' => $this->value_value,
      '#options' => $this->valueOptions,
      '#default_value' => $this->value,
    ];

    if (!empty($this->options['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
      // If we're configuring an exposed filter, add an - Any - option.
      if (!$exposed || empty($this->options['expose']['required'])) {
        $form['value']['#options'] = ['All' => $this->t('- Any -')] + $form['value']['#options'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = 'users_field_data.name';
    $or = new Condition('OR');

    if (empty($this->value) || $this->value == ['0']) {
      $or->condition($field, NULL, 'IS NOT NULL');
    }
    else {
      $or->condition($field, NULL, 'IS NULL');
    }

    $this->query->addWhere($this->options['group'], $or);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    // This filter depends on the current user.
    $contexts[] = 'user';

    return $contexts;
  }

}
