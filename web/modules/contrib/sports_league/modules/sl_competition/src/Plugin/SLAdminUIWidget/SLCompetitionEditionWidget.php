<?php
namespace Drupal\sl_competition\Plugin\SLAdminUIWidget;
use Drupal\sl_admin_ui\SLAdminUIWidgetBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a basic widget.
 *
 * @SLAdminUIWidget(
 *   id = "sl_competition_edition",
 *   name = @Translation("Competition Editions"),
 *   description = @Translation("Competitions editions have matches/standings"),
 *   bundle = "sl_competition_edition"
 * )
 */
class SLCompetitionEditionWidget extends SLAdminUIWidgetBase {

  function contentTable() {

    $ids = \Drupal::entityQuery('node')
      ->condition('type', $this->pluginDefinition['bundle'])

      ->execute();

    $rows = array();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    foreach ($nodes as $node) {
      $rows[] = array(
        $node->field_sl_administrative_title->value,
        Link::fromTextAndUrl($this->t('Add match'), Url::fromRoute('node.add', ['node_type' => 'sl_match'], ['query' => ['field_sl_competition' => $node->id()]]))
      );
    }

    return array(
      '#theme' => 'table',
      '#header' => array($this->t('Active competiton'), $this->t('Action')),
      '#rows' => $rows,
      '#empty' => t('No active competition editions')
    );
  }

}