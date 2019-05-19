<?php
/**
 * Created by PhpStorm.
 * User: mkalkbrenner
 * Date: 03.10.14
 * Time: 13:32
 */

namespace Drupal\themekey\Engine;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\themekey\EngineInterface;
use Drupal\themekey\PropertyManagerTrait;
use Drupal\themekey\OperatorManagerTrait;
use Drupal\themekey\RuleChainManagerTrait;
use Drupal\themekey\Entity\ThemeKeyRule;
use Drupal\themekey\ThemeKeyRuleInterface;

class Engine implements EngineInterface {

  use PropertyManagerTrait;
  use OperatorManagerTrait;
  use RuleChainManagerTrait;

  /**
   * The system theme config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $routeMatch;

  /**
   * Constructs a DefaultNegotiator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  public function getRouteMatch() {
    return $this->routeMatch;
  }

  /**
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function determineTheme(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
#var_dump($route_match);
    $ruleChainManager = $this->getRuleChainManager();
    $chain = $ruleChainManager->getOptimizedChain();

    return $chain ? $this->walkRuleChildren($chain) : NULL;
  }

  protected function walkRuleChildren($chain, $theme = NULL, $parent = 0) {
    $has_children = FALSE;
    foreach ($chain as $ruleId => $ruleMetaData) {
      if ($ruleMetaData['parent'] == $parent) {
        $has_children = TRUE;
        $rule = ThemeKeyRule::load($ruleId);
        if ($this->matchCondition($rule)) {
          $theme = $this->walkRuleChildren($chain, $rule->theme(), $ruleId);
          if ($theme) {
            return $theme;
          }
        }
      }
    }
    // No children: return theme of parent.
    // Has children: all children did not match => return no theme.
    return $has_children ? NULL : $theme;
  }


  /**
   * Detects if a ThemeKey rule matches to the current
   * page request.
   *
   * @param object $rule
   *   ThemeKey rule as object:
   *   - property
   *   - operator
   *   - value
   *
   * @return bool
   */
  protected function matchCondition(ThemeKeyRuleInterface $rule) {
    $operator = $this->getOperatorManager()
      ->createInstance($rule->operator());

    $property = $this->getPropertyManager()
      ->createInstance($rule->property());

    $property->setEngine($this);

    #drupal_set_message(print_r($property->getValues(), TRUE));

    $values = $property->getValues();
    $key = $rule->key();

    if (!is_null($key)) {
      if (isset($values[$key])) {
        return $operator->evaluate($values[$key], $rule->value());
      }
    }
    else {
      foreach ($values as $value) {
        if ($operator->evaluate($value, $rule->value())) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}
