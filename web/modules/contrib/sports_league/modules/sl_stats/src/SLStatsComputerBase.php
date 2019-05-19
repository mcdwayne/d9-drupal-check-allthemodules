<?php

namespace Drupal\sl_stats;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Drupal\views\ResultRow;
use Drupal\Core\Entity\EntityTypeManager;

abstract class SLStatsComputerBase extends PluginBase implements SLStatsComputerPluginInterface {

  use StringTranslationTrait;

  abstract public function isApplicable($player, $team);

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Fetches raw results from a view
   * @param $view_name
   * @param $display_id
   * @param $args
   * @return array
   */
  protected function getViewsResults($view_name, $display_id, $args) {
    $values = [];
    $view = Views::getview($view_name);
    if ($view) {
      $view->setDisplay($display_id);
      $view->setArguments($args);
      $view->execute();
      foreach ($view->result as $rid => $row) {
        foreach ($view->field as $fid => $field) {
          $values[$rid][$fid] = $field->getValue($row);
        }
      }
      return $values;
    }
  }


  /**
   * Constructs a SLStatsComputer object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    $this->node_manager = $entityTypeManager->getStorage('node');
    $this->stats_manager = $entityTypeManager->getStorage('sl_stats');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  protected function getTeamStatsType($node) {

    // direct
    if (!empty($node->field_sl_stats_type->value)) {
      return $node->field_sl_stats_type->value;
    }
    // parent team
    else if (!empty($node->field_sl_teams->entity->field_sl_stats_type)) {
      return $node->field_sl_teams->entity->field_sl_stats_type->value;
    }
    return;
  }
}