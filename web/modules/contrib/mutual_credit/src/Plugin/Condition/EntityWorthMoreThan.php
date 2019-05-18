<?php

namespace Drupal\mcapi\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Worth field is more than' condition.
 *
 * @Condition(
 *   id = "mcapi_worth_more_than",
 *   label = @Translation("Entity is worth more than"),
 *   category = @Translation("Community Accounting"),
 *   context = {
 *     "worth" = @ContextDefinition("mcapi_worth",
 *       label = @Translation("Worth"),
 *       description = @Translation("the value of the worth field"),
 *     )
 *   }
 * )
 */
class EntityWorthMoreThan extends RulesConditionBase {

  /**
   * {@inheritdoc}
   *
   * This shows at the top of the condition editing page.
   */
  public function summary() {
    $summary = $this->t(
      'Entity is worth at least %value',
      ['%value' => $this->getConfiguration()['worth']]
    );
    return (string)$summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'worth' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function doEvaluate($entity, $fieldname, $worth) {
    $entity = $this->getContextValue('entity');
    $fieldname = $this->getContextValue('fieldname');
    $worth = $this->getContextValue('worth');
    if (is_null($worth->value)) {
      return TRUE;
    }
    elseif ($entity->{$fieldname}->getValue($worth->curr_id) > $worth->value) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   */
  public function negate($negate = TRUE) {
    return parent::negate($negate);
  }
}
