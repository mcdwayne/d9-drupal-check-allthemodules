<?php

namespace Drupal\per_node_analytics\Plugin\HiddenTabTemplate;

use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTemplateAnon;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase;

/**
 * A default template with JQuery tabs.
 *
 * @HiddenTabTemplateAnon(
 *   id = "per_node_analytics"
 * )
 */
class PerNodeAnalyticsJQueryTabsTemplate extends HiddenTabTemplatePluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::id()
   */
  protected $PID = 'per_node_analytics';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::label()
   */
  protected $HTPLabel = 'Per Node Analytics';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::description()
   */
  protected $HTPDescription = 'TODO';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::weight()
   */
  protected $HTPWeight = 0;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = ['general'];

  /**
   * See templateFile().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase::templateFile()
   */
  protected $templateFile = 'per-node-analytics-jquery-tabs';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->templateFilePath = drupal_get_path('module', 'per_node_analytics') . '/templates';
    $this->regions = [
      'reg_0' => t('Weekly'),
      'reg_1' => t('Monthly'),
      'reg_2' => t('Yearly'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibrary(): array {
    return [
      'library' => ['per_node_analytics/template.jquery_tabs'],
    ];
  }

}
