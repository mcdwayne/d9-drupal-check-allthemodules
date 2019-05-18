<?php

namespace Drupal\access_filter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\access_filter\FilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;

/**
 * Defines the Filter entity.
 *
 * @ConfigEntityType(
 *   id = "access_filter",
 *   label = @Translation("Access filter"),
 *   handlers = {
 *     "list_builder" = "Drupal\access_filter\AccessFilterListBuilder",
 *     "form" = {
 *       "default" = "Drupal\access_filter\Form\EditForm",
 *       "delete" = "Drupal\access_filter\Form\DeleteForm",
 *     }
 *   },
 *   config_prefix = "filter",
 *   admin_permission = "manage access filters",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "status" = "status",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "collection" = "/admin/config/people/access_filter",
 *     "edit-form" = "/admin/config/people/access_filter/{filter}/edit",
 *     "delete-form" = "/admin/config/people/access_filter/{filter}/delete",
 *   }
 * )
 */
class Filter extends ConfigEntityBase implements FilterInterface {

  /**
   * The filter ID.
   *
   * @var string
   */
  public $id;

  /**
   * The filter name.
   *
   * @var string
   */
  public $name;

  /**
   * YAML serialized conditions.
   *
   * @var string
   */
  public $conditions;

  /**
   * YAML serialized rules.
   *
   * @var string
   */
  public $rules;

  /**
   * YAML serialized response settings.
   *
   * @var string
   */
  public $response;

  /**
   * Parsed conditions.
   *
   * @var \Drupal\access_filter\Plugin\ConditionInterface[]
   */
  public $parsedConditions;

  /**
   * Parsed rules.
   *
   * @var \Drupal\access_filter\Plugin\RuleInterface[]
   */
  public $parsedRules;

  /**
   * Parsed response settings.
   *
   * @var array
   */
  public $parsedResponse;

  /**
   * Relative weight of this filter.
   *
   * @var int
   */
  public $weight;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\access_filter\Entity\Filter[]
   *   An array of Filter entities.
   */
  public static function loadMultiple(array $ids = NULL) {
    /** @var \Drupal\access_filter\Entity\Filter[] $filters */
    $filters = parent::loadMultiple($ids);
    uasort($filters, function ($a, $b) {
      if ($a->weight == $b->weight) {
        return 0;
      }
      return ($a->weight < $b->weight) ? -1 : 1;
    });
    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function parse() {
    /* @var \Drupal\access_filter\Plugin\AccessFilterPluginManager $condition_plugin_manager */
    $condition_plugin_manager = \Drupal::service('plugin.manager.access_filter.condition');
    $condition_plugins = $condition_plugin_manager->getDefinitions();

    $parsed_conditions = $this->parseYaml($this->conditions);
    $this->parsedConditions = [];
    foreach ($parsed_conditions as $condition) {
      $plugin_id = $condition['type'];
      if (isset($condition_plugins[$plugin_id])) {
        $this->parsedConditions[] = $condition_plugin_manager->createInstance($plugin_id, $condition);
      }
    }

    /* @var \Drupal\access_filter\Plugin\AccessFilterPluginManager $rule_plugin_manager */
    $rule_plugin_manager = \Drupal::service('plugin.manager.access_filter.rule');
    $rule_plugins = $rule_plugin_manager->getDefinitions();

    $parsed_rules = $this->parseYaml($this->rules);
    $this->parsedRules = [];
    foreach ($parsed_rules as $rule) {
      $plugin_id = $rule['type'];
      if (isset($rule_plugins[$plugin_id])) {
        $this->parsedRules[] = $rule_plugin_manager->createInstance($plugin_id, $rule);
      }
    }

    $this->parsedResponse = $this->parseYaml($this->response) + [
      'code' => 403,
      'redirect_url' => NULL,
      'body' => NULL,
    ];
  }

  /**
   * Parses YAML string.
   *
   * @param string $string
   *   The YAML string to parse.
   * @param bool $safe
   *   Indicates to return safe value.
   *
   * @return mixed
   *   An parsed array if succeeded.
   *   On failure, empty array if $safe is TRUE or FALSE otherwise.
   */
  private function parseYaml($string, $safe = TRUE) {
    $parser = new Parser();
    try {
      $parsed = $parser->parse($string);
    }
    catch (\Exception $e) {
      $parsed = NULL;
    }

    if ($parsed === NULL) {
      if ($safe) {
        $parsed = [];
      }
      else {
        $parsed = FALSE;
      }
    }
    return $parsed;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed(Request $request) {
    $is_matched = FALSE;
    foreach ($this->parsedConditions as $condition) {
      $is_matched = $condition->isMatched($request);
      if ($condition->isNegated()) {
        $is_matched = !$is_matched;
      }
      if ($is_matched) {
        break;
      }
    }

    if (!$is_matched) {
      return TRUE;
    }

    $is_allowed = TRUE;
    foreach ($this->parsedRules as $rule) {
      $result = $rule->check($request);
      if ($result->isAllowed()) {
        $is_allowed = TRUE;
      }
      elseif ($result->isForbidden()) {
        $is_allowed = FALSE;
      }
    }
    return $is_allowed;
  }

}
