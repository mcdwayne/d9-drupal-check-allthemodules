<?php

namespace Drupal\group_purl\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* Provides a 'Group purl condition' condition to enable a condition based in module selected status.
*
* @Condition(
*   id = "group_purl_condition",
*   label = @Translation("Group purl condition"),
*   context = {
*     "group" = @ContextDefinition("entity:group", default = 0, label = @Translation("group"))
*   }
* )
*
*/
class GroupPurlCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;
/**
* {@inheritdoc}
*/
public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
{
  return new static(
    $container->get('entity_type.manager')->getStorage('group'),
    $configuration,
    $plugin_id,
    $plugin_definition
  );
}

/**
 * Creates a new GroupPurlCondition object.
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
 public function __construct(EntityStorageInterface $entityStorage, array $configuration, $plugin_id, $plugin_definition) {
   $this->entityStorage = $entityStorage;
   parent::__construct($configuration, $plugin_id, $plugin_definition);
 }

 /**
   * {@inheritdoc}
   */
 public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
   $options = [];
   /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
   $groups = $this->entityStorage->loadMultiple();
   foreach($groups as $group) {
       $options[$group->id()] = $group->label();
   }

   $form['groups'] = [
       '#type' => 'checkboxes',
       '#title' => $this->t('Select a group to validate'),
       '#default_value' => $this->configuration['groups'],
       '#options' => $options,
       '#description' => $this->t('Group to evaluate condition.'),
   ];

   return parent::buildConfigurationForm($form, $form_state);
 }

/**
 * {@inheritdoc}
 */
 public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
   $this->configuration['groups'] = array_filter($form_state->getValue('groups'));
   parent::submitConfigurationForm($form, $form_state);
 }

/**
 * {@inheritdoc}
 */
 public function defaultConfiguration() {
    return ['groups' => []] + parent::defaultConfiguration();
 }

/**
  * Evaluates the condition and returns TRUE or FALSE accordingly.
  *
  * @return bool
  *   TRUE if the condition has been met, FALSE otherwise.
  */
  public function evaluate() {
    if (empty($this->configuration['groups']) && !$this->isNegated()) {
      return TRUE;
    }
    $group = $this->getContextValue('group');

    return !empty($group) && !empty($this->configuration['groups'][$group->id()]);
  }

/**
 * Provides a human readable summary of the condition's configuration.
 */
 public function summary()
 {
   if (count($this->configuration['groups']) > 1) {
     $groups = $this->configuration['groups'];
     $last = array_pop($groups);
     $groups = implode(', ', $groups);
     return $this->t('The group is @group or $last', ['@group' => $groups, '@last' => $last]);
   }
   $group = reset($this->configuration['groups']);
   return $this->t('The group is @group.', ['@group' => $group]);
 }

}
