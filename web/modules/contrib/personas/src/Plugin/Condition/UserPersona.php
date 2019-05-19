<?php

namespace Drupal\personas\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\personas\PersonaUtility;

/**
 * Provides a 'Persona' condition.
 *
 * @Condition(
 *   id = "persona",
 *   label = @Translation("Persona"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class UserPersona extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['personas'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('When the user has the following personas'),
      '#default_value' => $this->configuration['personas'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', personas_get_names()),
      '#description' => $this->t('If you select no personas, the condition will evaluate to TRUE for all users.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'personas' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['personas'] = array_filter($form_state->getValue('personas'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the persona labels. They will be sanitized below.
    $personas = array_intersect_key(personas_get_names(), $this->configuration['personas']);
    if (count($personas) > 1) {
      $personas = implode(', ', $personas);
    }
    else {
      $personas = reset($personas);
    }
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user is not a member of @personas', ['@personas' => $personas]);
    }
    else {
      return $this->t('The user is a member of @personas', ['@personas' => $personas]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['personas']) && !$this->isNegated()) {
      return TRUE;
    }
    $user = $this->getContextValue('user');

    $wanted = array_keys($this->configuration['personas']);
    $have = PersonaUtility::personaNames(PersonaUtility::fromUser($user));
    $intersection = array_intersect($wanted, $have);

    $has_required_personas = count($intersection) > 0;

    return $has_required_personas;
  }

}
