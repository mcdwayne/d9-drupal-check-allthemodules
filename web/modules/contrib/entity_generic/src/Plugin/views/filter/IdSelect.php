<?php

namespace Drupal\entity_generic\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler for entity ids using select.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_generic_id_select")
 */
class IdSelect extends InOperator {

  protected $alwaysMultiple = TRUE;

  protected function valueForm(&$form, FormStateInterface $form_state) {
    $entities = $this->value ? \Drupal::entityTypeManager()->getStorage($this->getEntityType())->loadMultiple($this->value) : [];
    $default_value = EntityAutocomplete::getEntityLabels($entities);

    $options = [];
    $query = \Drupal::entityQuery($this->getEntityType())
      ->sort(\Drupal::entityTypeManager()->getDefinition($this->getEntityType())->getKey('label'));
    $entities = \Drupal::entityTypeManager()->getStorage($this->getEntityType())->loadMultiple($query->execute());
    foreach ($entities as $entity) {
      $options[$entity->id()] = \Drupal::entityManager()->getTranslationFromContext($entity)->label();
    }

    $default_value = (array) $this->value;

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (!empty($this->options['expose']['reduce'])) {
        $options = $this->reduceValueOptions($options);

        if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
          $default_value = [];
        }
      }

      if (empty($this->options['expose']['multiple'])) {
        if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
          $default_value = 'All';
        }
        elseif (empty($default_value)) {
          $keys = array_keys($options);
          $default_value = array_shift($keys);
        }
        // Due to #1464174 there is a chance that array('') was saved in the admin ui.
        // Let's choose a safe default value.
        elseif ($default_value == ['']) {
          $default_value = 'All';
        }
        else {
          $copy = $default_value;
          $default_value = array_shift($copy);
        }
      }
    }

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entities'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#size' => min(9, count($options)),
      '#default_value' => $default_value,
    ];

    $user_input = $form_state->getUserInput();
    if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
      $user_input[$identifier] = $default_value;
      $form_state->setUserInput($user_input);
    }

  }

  protected function valueValidate($form, FormStateInterface $form_state) {
    $ids = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $ids[] = $value['target_id'];
      }
      sort($ids);
    }
    $form_state->setValue(['options', 'value'], $ids);
  }

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

  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    // We only validate if they've chosen the text field style.
    if ($form_state->getValue($identifier) != 'All') {
      $this->validated_exposed_input = (array) $form_state->getValue($identifier);
    }

  }

  protected function valueSubmit($form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    return $this->valueOptions;
  }

  public function adminSummary() {
    // set up $this->valueOptions for the parent summary
    $this->valueOptions = [];

    if ($this->value) {
      $result = \Drupal::entityTypeManager()->getStorage($this->getEntityType())
        ->loadByProperties(['id' => $this->value]);
      foreach ($result as $entity) {
        $this->valueOptions[$entity->id()] = $entity->label();
      }
    }

    return parent::adminSummary();
  }

}
