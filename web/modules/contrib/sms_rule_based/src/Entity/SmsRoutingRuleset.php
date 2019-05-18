<?php

namespace Drupal\sms_rule_based\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection;

/**
 * @ConfigEntityType(
 *   id = "sms_routing_ruleset",
 *   label = @Translation("SMS Routing Ruleset"),
 *   handlers = {
 *     "form" = {
 *       "default" = "\Drupal\sms_rule_based\Form\SmsRoutingRulesetForm",
 *       "delete" = "\Drupal\sms_rule_based\Form\SmsRoutingRulesetDeleteForm",
 *     },
 *     "list_builder" = "\Drupal\sms_rule_based\Form\SmsRoutingRulesetListForm",
 *   },
 *   admin_permission = "administer rule-based routing",
 *   config_prefix = "ruleset",
 *   entity_keys = {
 *     "id" = "name",
 *     "weight" = "weight",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/sms_rule_based/ruleset/edit/{sms_routing_ruleset}",
 *     "delete-form" = "/admin/config/sms_rule_based/ruleset/delete/{sms_routing_ruleset}",
 *   },
 * );
 */
class SmsRoutingRuleset extends ConfigEntityBase implements EntityWithPluginCollectionInterface {

  /**
   * The name of the rule-based routing ruleset.
   *
   * @var string
   */
  protected $name;

  /**
   * The label of the rule-based routing ruleset.
   *
   * @var string
   */
  protected $label;

  /**
   * A description of what the routing ruleset does.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of the routing ruleset in the stack of rulesets.
   * 
   * @var float
   */
  protected $weight;

  /**
   * Whether this ruleset is enabled to run or not.
   *
   * @var boolean
   */
  protected $enabled;

  /**
   * The list of rules in this ruleset.
   *
   * @var array
   *
   * @see SmsRoutingRuleset::addRule()
   */
  protected $rules = array();

  /**
   * All the rules must be true for the ruleset to apply.
   *
   * If false, any single rule that passes will allow the ruleset to apply.
   *
   * @var boolean
   */
  protected $_ALL_TRUE_;

  /**
   * Gateway for which this rule applies.
   *
   * @var string
   */
  protected $gateway;

  /**
   * The collection of the SMS routing rules in this ruleset.
   *
   * @var \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * @return \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection
   */
  protected function getPluginCollection() {
    if (!isset($this->pluginCollection)) {
      $this->pluginCollection = new SmsRoutingRulePluginCollection(
        \Drupal::service('plugin.manager.sms_routing_rule'),
        $this->rules
      );
    }
    return $this->pluginCollection;
  }

  /**
   * Gets a SMS routing rule of specified name.
   *
   * @param string $name
   *   The name of the SMS routing rule.
   *
   * @return \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface
   */
  public function getRule($name) {
    return $this->getPluginCollection()->get($name);
  }

  /**
   * Gets all the routing rules in this ruleset.
   *
   * @return \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginCollection|
   *   \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface[]
   */
  public function getRules() {
    return $this->getPluginCollection();
  }

  /**
   * Adds a new sms routing rule to the ruleset.
   *
   * @param array $rule
   *   An array containing information to build the rule, as follows:
   *   - name: The machine-name of the rule.
   *   - enabled: Whether the rule is enabled to run or not.
   *   - operator: The logical comparison operator which would be used to evaluate
   *     the rule.
   *   - operand: The value which will be used to evaluate the rule against the
   *     given parameter.
   *   - negated: Whether the rule's expression should be negated.
   *   - type: The rule type, basically the SmsRoutingRulePluginInterface plugin
   *     that is used to instantiate the rule.
   */
  public function addRule(array $rule) {
    $this->rules[$rule['name']] = $rule;
    $this->getPluginCollection()->addInstanceId($rule['name'], $rule);
  }

  /**
   * Removes a specified sms routing rule from the ruleset.
   *
   * @param string $rule_name
   *   The name of the rule to be removed.
   */
  public function removeRule($rule_name) {
    unset($this->rules[$rule_name]);
    $this->getPluginCollection()->removeInstanceId($rule_name);
  }

  /**
   * Directly sets the rules in the ruleset array.
   *
   * @param array $rules
   *   The array of rules.
   */
  public function setRules(array $rules) {
    $this->rules = $rules;
    $this->pluginCollection = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['rules' => $this->getPluginCollection()];
  }

}
