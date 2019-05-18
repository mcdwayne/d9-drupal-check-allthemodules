<?php

declare(strict_types = 1);

namespace Drupal\date_recur_ss\Plugin\DateRecurInterpreter;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\date_recur\Plugin\DateRecurInterpreterPluginBase;
use Recurr\Rule;
use Recurr\Transformer\TextTransformer;
use Recurr\Transformer\Translator;

/**
 * Provides an interpreter implemented by simshaun/recurr.
 *
 * @DateRecurInterpreter(
 *  id = "ss",
 *  label = @Translation("SS interpreter"),
 * )
 *
 * @ingroup SShaunPhpRrule
 */
class SsInterpreter extends DateRecurInterpreterPluginBase {

  use DependencyTrait;

  /**
   * Constructs a new SsInterpreter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function interpret(array $rules, $language): string {
    if (!in_array($language, $this->supportedLanguages())) {
      throw new \Exception('Language not supported.');
    }

    $strings = [];
    $translator = new Translator($language);
    $textTransformerBase = new TextTransformer($translator);
    /** @var \Drupal\date_recur\DateRecurRuleInterface $rule */
    foreach ($rules as $rule) {
      $textTransformer = clone $textTransformerBase;
      $parts = $rule->getParts();
      $dtStart = $parts['DTSTART'] ?? NULL;
      unset($parts['DTSTART']);
      assert(isset($dtStart));
      $rrule = Rule::createFromArray($parts, $dtStart);
      $strings[] = $textTransformer->transform($rrule);
    }

    return implode(', ', $strings);
  }

  /**
   * {@inheritdoc}
   */
  public function supportedLanguages(): array {
    return [
      'da',
      'de',
      'el',
      'en',
      'es',
      'eu',
      'fr',
      'it',
      'nl',
      'pt-br',
      'sv',
      'tr',
    ];
  }

}
