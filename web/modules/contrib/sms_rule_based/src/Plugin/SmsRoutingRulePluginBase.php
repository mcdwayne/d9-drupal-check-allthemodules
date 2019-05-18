<?php

namespace Drupal\sms_rule_based\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

abstract class SmsRoutingRulePluginBase extends PluginBase implements SmsRoutingRulePluginInterface {

  /**
   * Constants representing operators for expressions.
   */

  /**
   * true if the supplied parameter is equal to the operand.
   */
  const EQ = 'EQ';

  /**
   * true if the supplied parameter is less than the operand.
   */
  const LE = 'LE';

  /**
   * true if the supplied parameter is less than or equal to the operand.
   */
  const LT = 'LT';

  /**
   * true if the supplied parameter is greater than the operand.
   */
  const GT = 'GT';

  /**
   * true if the supplied parameter is greater than or equal to the operand.
   */
  const GE = 'GE';

  /**
   * true if the supplied parameter is found within the operand (the operand would 
   * then be a comma-separated list).
   */
  const IN = 'IN';

  /**
   * true if the supplied parameter matches a wildcard-type expression in 
   * the operand.
   */
  const LK = 'LK';

  /**
   * true if the supplied parameter matches the regular expression in the 
   * operand.
   */
  const RX = 'RX';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->configuration['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->configuration['enabled'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperator() {
    return $this->configuration['operator'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperand() {
    return $this->configuration['operand'];
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOperator() {
    return static::getLongOperatorTypes()[$this->getOperator()];
  }

  /**
   * {@inheritdoc}
   */
  public function getReadableOperand() {
    return $this->getOperand();
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated() {
    return $this->configuration['negated'];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget() {
    return [
      '#type' => 'textfield',
      '#title' => $this->pluginDefinition['label'],
      '#description' => $this->pluginDefinition['description'],
      '#title_display' => 'invisible',
      '#description_display' => 'invisible',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public abstract function match(array $numbers, array $context);

  public function toString() {
    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function processWidgetValue($form_value) {
    return $form_value;
  }

  /**
   * Checks whether the parameter satisfies the rule provided.
   *
   * @param mixed $param
   *   A parameter to evaluate against the rule.
   *
   * @return bool
   *   The result of the check - true or false.
   */
  protected function satisfiesExpression($param) {
    $ret = false;
    switch ($this->configuration['operator']) {
      case static::EQ:
        $ret = ($param == $this->configuration['operand']);
        break;
      case static::LT:
        $ret = ($param < $this->configuration['operand']);
        break;
      case static::LE:
        $ret = ($param <= $this->configuration['operand']);
        break;
      case static::GT:
        $ret = ($param > $this->configuration['operand']);
        break;
      case static::GE:
        $ret = ($param >= $this->configuration['operand']);
        break;
      case static::IN:
        $patterns = explode(',', $this->configuration['operand']);
        $ret = false;
        foreach ($patterns as $pattern) {
          // Replace common wildcards with equivalent regular expressions, then
          // use regex match.
          $exp = str_replace('%', '.*', str_replace('?', '.', trim($pattern)));
          // Use strict token matching.
          $ret = (preg_match("/^$exp\$/i", $param) === 1);
          if ($ret) {
            break;
          }
        }
        break;
      case static::LK:
        // Replace common wildcards with equivalent regular expressions, then
        // use regex match.
        $exp = str_replace('%', '.*', str_replace('?', '.', $this->configuration['operand']));
        $ret = (preg_match("/^$exp\$/i", $param) === 1);
        break;
      case static::RX:
        $ret = (preg_match("/{$this->configuration['operand']}/i", $param) === 1);
        break;
    }
    if ($this->configuration['negated'] && isset($ret)) {
      $ret = !$ret;
    }
    return $ret && true;
  }

  /**
   * Specifies the different operator types.
   */
  public static function getOperatorTypes() {
    return array(
      static::EQ => static::EQ,
      static::LT => static::LT,
      static::LE => static::LE,
      static::GT => static::GT,
      static::GE => static::GE,
      static::IN => static::IN,
      static::LK => static::LK,
      static::RX => static::RX,
    );
  }

  /**
   * Provides the translated long names of the different operator types.
   */
  public static function getLongOperatorTypes() {
    return array(
      static::EQ => new TranslatableMarkup('is'),
      static::LT => new TranslatableMarkup('is less than'),
      static::LE => new TranslatableMarkup('is less or equal to'),
      static::GT => new TranslatableMarkup('is more than'),
      static::GE => new TranslatableMarkup('is more or equal to'),
      static::IN => new TranslatableMarkup('is any of'),
      static::LK => new TranslatableMarkup('is like'),
      static::RX => new TranslatableMarkup('matches'),
    );
  }

  /**
   * Provides a printable help string for the various operator types.
   */
  public static function getOperatorTypesHelp() {
    return <<<EOF
EQ: equal to
LT: less than
LE: less than or equal to
GT: greater than
GE: greater than or equal to
IN: any of (comma-separated patterns)
LK: looks like (wildcards % and ?)
RX: full regular expresson
EOF;
  }

}
