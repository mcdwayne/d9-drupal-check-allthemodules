<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\ConditionFormBase.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a base form for editing and adding a condition.
 */
abstract class ConditionFormBase extends FormBase {

  /**
   * The block page this condition belongs to.
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $blockPage;

  /**
   * The condition used by this form.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * Prepares the condition used by this form.
   *
   * @param string $condition_id
   *   Either a condition ID, or the plugin ID used to create a new
   *   condition.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The condition object.
   */
  abstract protected function prepareCondition($condition_id);

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitButtonText();

  /**
   * Returns the text to use for the submit message.
   *
   * @return string
   *   The submit message text.
   */
  abstract protected function submitMessageText();

  /**
   * @return \Drupal\block_page\ContextHandler
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $condition_id = NULL) {
    $this->blockPage = $block_page;
    $this->condition = $this->prepareCondition($condition_id);

    // Allow the condition to add to the form.
    $form['condition'] = $this->condition->buildConfigurationForm(array(), $form_state);
    $form['condition']['#tree'] = TRUE;

    if ($this->condition instanceof ContextAwarePluginInterface) {
      $form['context_assignments'] = $this->addContextAssignmentElement($this->condition, $this->blockPage->getContexts());
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->submitButtonText(),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * Builds a form element for assigning a context to a given slot.
   *
   * @param \Drupal\Component\Plugin\ContextAwarePluginInterface $condition
   *   The context-aware condition plugin.
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts.
   *
   * @return array
   *   A form element for assigning context.
   */
  protected function addContextAssignmentElement(ContextAwarePluginInterface $condition, $contexts) {
    $element = array();
    $element['#tree'] = TRUE;
    foreach ($condition->getContextDefinitions() as $context_slot => $definition) {
      // Assume the requirement is required if unspecified.
      $definition['required'] = isset($definition['required']) ? $definition['required'] : TRUE;
      $definition = new DataDefinition($definition);

      $valid_contexts = $this->contextHandler()->getValidContexts($contexts, $definition);
      $options = array();
      foreach ($valid_contexts as $context_id => $context) {
        $context_definition = new DataDefinition($context->getContextDefinition());
        $options[$context_id] = $context_definition->getLabel();
      }

      // @todo Find a better way to load context assignments.
      $configuration = $condition->getConfiguration();
      $assignments = isset($configuration['context_assignments']) ? $configuration['context_assignments'] : array();

      $element[$context_slot] = array(
        '#title' => $this->t('Select a @context value:', array('@context' => $context_slot)),
        '#type' => 'select',
        '#options' => $options,
        '#required' => $definition->isRequired(),
        '#default_value' => !empty($assignments[$context_slot]) ? $assignments[$context_slot] : '',
      );
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Allow the condition to validate the form.
    $condition_values = array(
      'values' => &$form_state['values']['condition'],
    );
    $this->condition->validateConfigurationForm($form, $condition_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Allow the condition to submit the form.
    $condition_values = array(
      'values' => &$form_state['values']['condition'],
    );
    $this->condition->submitConfigurationForm($form, $condition_values);

    if (!empty($form_state['values']['context_assignments'])) {
      // @todo Consider creating a ContextAwareConditionPluginBase to handle this.
      $configuration = $this->condition->getConfiguration();
      $configuration['context_assignments'] = $form_state['values']['context_assignments'];
      $this->condition->setConfiguration($configuration);
    }

    // Set the submission message.
    drupal_set_message($this->submitMessageText());
  }

}
