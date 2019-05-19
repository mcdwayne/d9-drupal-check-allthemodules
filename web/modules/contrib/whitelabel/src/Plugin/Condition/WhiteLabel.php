<?php

namespace Drupal\whitelabel\Plugin\Condition;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'White label enabled' condition.
 *
 * @Condition(
 *   id = "whitelabel",
 *   label = @Translation("White label"),
 *   context = {
 *     "whitelabel" = @ContextDefinition("entity:whitelabel", label = @Translation("Active white label"))
 *   }
 * )
 */
class WhiteLabel extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable if'),
      '#default_value' => $this->configuration['enabled'],
      '#options' => [
        'white labeled' => $this->t('Page is white labeled'),
        'not white labeled' => $this->t('Page is not white labeled'),
      ],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled'] = array_filter($form_state->getValue('enabled'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (in_array('white labeled', $this->configuration['enabled']) && in_array('not white labeled', $this->configuration['enabled'])) {
      return $this->t('Page is either white labeled or not');
    }
    elseif (in_array('white labeled', $this->configuration['enabled'])) {
      return $this->t('Page is white labeled');
    }
    elseif (in_array('not white labeled', $this->configuration['enabled'])) {
      return $this->t('Page is not white labeled');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // No conditions enforced.
    if (empty($this->configuration['enabled']) && !$this->isNegated()) {
      return TRUE;
    }

    try {
      $white_label = $this->getContextValue('whitelabel');
      if ($white_label && in_array('white labeled', $this->configuration['enabled'])) {
        return TRUE;
      }
    }
    // If there was no white label provided.
    catch (ContextException $exception) {
      // Check of no white label was expected.
      if (in_array('not white labeled', $this->configuration['enabled'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['enabled' => []] + parent::defaultConfiguration();
  }

}
