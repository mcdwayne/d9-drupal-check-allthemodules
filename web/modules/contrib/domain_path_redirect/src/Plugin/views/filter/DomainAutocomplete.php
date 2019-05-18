<?php

namespace Drupal\domain_path_redirect\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\domain\Entity\Domain;

/**
 * Provides a autocomplete list of Domain entities.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_autocomplete")
 */
class DomainAutocomplete extends InOperator {

  protected $alwaysMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $domains = $this->value ? Domain::loadMultiple($this->value) : [];
    $default_value = EntityAutocomplete::getEntityLabels($domains);
    $form['value'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Domains'),
      '#description' => $this->t('Enter a comma separated list of domain names.'),
      '#target_type' => 'domain',
      '#tags' => TRUE,
      '#default_value' => $default_value,
      '#process_default_value' => $this->isExposed(),
    ];

    $user_input = $form_state->getUserInput();
    if ($form_state->get('exposed') && !isset($user_input[$this->options['expose']['identifier']])) {
      $user_input[$this->options['expose']['identifier']] = $default_value;
      $form_state->setUserInput($user_input);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $domains = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $domains[] = $value['target_id'];
      }
      sort($domains);
    }
    $form_state->setValue(['options', 'value'], $domains);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $rc = parent::acceptExposedInput($input);

    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validated_exposed_input)) {
        $this->value = $this->validated_exposed_input;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];
    $input = $form_state->getValue($identifier);

    if ($this->options['is_grouped'] && isset($this->options['group_info']['group_items'][$input])) {
      $this->operator = $this->options['group_info']['group_items'][$input]['operator'];
      $input = $this->options['group_info']['group_items'][$input]['value'];
    }

    $domains = [];
    $values = $form_state->getValue($identifier);
    if ($values && (!$this->options['is_grouped'] || ($this->options['is_grouped'] && ($input != 'All')))) {
      foreach ($values as $value) {
        $domains[] = $value['target_id'];
      }
    }

    if ($domains) {
      $this->validated_exposed_input = $domains;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    // Set up $this->valueOptions for the parent summary.
    $this->valueOptions = [];

    if ($this->value) {
      $result = \Drupal::entityTypeManager()->getStorage('domain')
        ->loadMultiple($this->value);
      foreach ($result as $domain) {
        if ($domain->id()) {
          $this->valueOptions[$domain->id()] = $domain->label();
        }
      }
    }

    return parent::adminSummary();
  }

}
