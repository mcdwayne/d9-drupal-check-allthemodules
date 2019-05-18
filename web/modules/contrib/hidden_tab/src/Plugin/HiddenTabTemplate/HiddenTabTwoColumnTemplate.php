<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabTemplate;

use Drupal\hidden_tab\Plugable\Annotation\HiddenTabTemplateAnon;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginBase;

/**
 * A default template with two regions.
 *
 * @HiddenTabTemplateAnon(
 *   id = "hidden_tab_two_column"
 * )
 */
class HiddenTabTwoColumnTemplate extends HiddenTabTemplatePluginBase {

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::id()
   */
  protected $PID = 'hidden_tab_two_column';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\HiddenTabTemplatePluginBase::label()
   */
  protected $HTPLabel = 'Hidden Tab - Two Column / Two Regions';

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
  protected $templateFile = 'two-column';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration,
      $plugin_id,
      $plugin_definition);
    $this->regions = [
      'reg_0' => t('Reg 0'),
      'reg_1' => t('Reg 1'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibrary(): array {
    return [
      'library' => ['hidden_tab/template.hidden_tab_two_column_template'],
    ];
  }

}
