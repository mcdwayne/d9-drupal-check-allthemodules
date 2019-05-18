<?php

namespace Drupal\context_node_template\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\node\Entity\Node;

/**
* Provides a 'Context node template condition' condition to enable a condition based in node template selected status.
*
* @Condition(
*   id = "context_node_template",
*   label = @Translation("Context node template"),
*   context = {
*     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
*   }
* )
*
*/
class ContextNodeTemplateCondition extends ConditionPluginBase {

/**
* {@inheritdoc}
*/
public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
{
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition
    );
}

/**
 * Creates a new ExampleCondition instance.
 *
 * @param array $configuration
 *   The plugin configuration, i.e. an array with configuration values keyed
 *   by configuration option name. The special key 'context' may be used to
 *   initialize the defined contexts by setting it to an array of context
 *   values keyed by context names.
 * @param string $plugin_id
 *   The plugin_id for the plugin instance.
 * @param mixed $plugin_definition
 *   The plugin implementation definition.
 */
 public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
 }

 /**
   * {@inheritdoc}
   */
 public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $templates = _get_page_templates();
    if(isset($templates['default'])){
      unset($templates['default']);
    }

    $form['node_template'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Page Template'),
      '#default_value' => $this->configuration['node_template'],
      '#options' => $templates,
      '#description' => $this->t('Set one node page template name per line, eg: page--xxx, this template name should be a file that already exists in your current theme directory, eg: page--xxx.html.twig'),
    );

    return parent::buildConfigurationForm($form, $form_state);
 }

/**
 * {@inheritdoc}
 */
 public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
     $this->configuration['node_template'] = $form_state->getValue('node_template');
     parent::submitConfigurationForm($form, $form_state);
 }

/**
 * {@inheritdoc}
 */
 public function defaultConfiguration() {
    return array('node_template' => '') + parent::defaultConfiguration();
 }

/**
  * Evaluates the condition and returns TRUE or FALSE accordingly.
  *
  * @return bool
  *   TRUE if the condition has been met, FALSE otherwise.
  */
  public function evaluate() {      
      if (empty($this->configuration['node_template']) && !$this->isNegated()) {
          return TRUE;
      }

      $node = $this->getContextValue('node');
      
      $result = db_query('SELECT template FROM {node_template} WHERE nid = :nid', array(':nid' => $node->id()))->fetchObject();

      if(empty($node)){
          return FALSE;
      }

      $node_template = array_filter($this->configuration['node_template']);

      if(in_array($result->template, $node_template)){
          return TRUE;
      }
      
      return FALSE;
  }

/**
 * Provides a human readable summary of the condition's configuration.
 */
 public function summary() {
    $node_template = array_map('trim', explode("\n", $this->configuration['node_template']));
    $node_template = implode(', ', $node_template);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following templates: @templates', array('@templates' => $node_template));
    }
    return $this->t('Return true on the following templates: @templates', array('@templates' => $node_template));
 }

}
