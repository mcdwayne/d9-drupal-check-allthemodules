<?php

namespace Drupal\node_accessibility\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node_accessibility\TypeSettingsStorage;
use Drupal\node_accessibility\PerformValidation;
use Drupal\quail_api\QuailApiSettings;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Perform accessibility validation on a node.
 *
 * @Action(
 *   id = "node_accessibility_validate_action",
 *   label = @Translation("Perform Accessibility Validation on Node"),
 *   type = "node"
 * )
 */
class ValidateNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (is_null($entity)) {
      return;
    }

    $id_node = $entity->id();
    $id_revision = $entity->id();
    $settings = TypeSettingsStorage::loadByNodeAsArray($id_node);

    if (is_null($id_revision)) {
      $results = PerformValidation::nodes([$id_node], NULL, NULL, $settings['standards']);
    }
    else {
      $results = PerformValidation::node_revisions([$id_revision => $id_node], NULL, NULL, $settings['standards']);
    }

    if (empty($settings['node_type'])) {
      return;
    }

    if (empty($settings['enabled']) || $settings['enabled'] == 'disabled') {
      return;
    }

    if (array_key_exists($id_node, $results) && !empty($results[$id_node])) {
      $severitys = QuailApiSettings::get_severity();
      $methods = QuailApiSettings::get_validation_methods();

      if ($settings['method'] == 'quail_api_method_manual' || $settings['method'] == 'quail_api_method_immediate') {
        // do not process validation action if not saving to the database.
        return;
      }

      $revision = reset($results[$id_node]);
      unset($results);

      if (empty($revision['report'])) {
        unset($revision);

        $markup = $this->t('No accessibility violations have been detected.');
      }
      else {
        $reports = $revision['report'];
        $total = $revision['total'];
        unset($revision);

        if (empty($settings['format_results'])) {
          $format_results = \Drupal::config('quail_api.settings')->get('filter_format');
        }
        else {
          $format_results = $settings['format_results'];
        }

        if (empty($settings['title_block'])) {
          $title_block = \Drupal::config('quail_api.settings')->get('title_block');
        }
        else {
          $title_block = $settings['title_block'];
        }

        if (empty($title_block)) {
          $title_block = 'h3';
        }

        // the reason this is converted to markup is because the generated
        // markup is intended to be saved to the database. This is not a
        // cache, but a renderred copy of the data for archival and
        // validation purposes.
        $markup = '';
        foreach ($reports as $severity => $severity_results) {
          $theme_array = [
            '#theme' => 'quail_api_results',
            '#quail_severity_id' => $severity,
            '#quail_severity_array' => $severitys[$severity],
            '#quail_severity_results' => $severity_results,
            '#quail_markup_format' => $format_results,
            '#quail_title_block' => $title_block,
            '#quail_display_title' => TRUE,
            '#attached' => [
              'library' => [
                'node_accessibility/results-theme',
              ],
            ],
          ];

          $markup .= \Drupal::service('renderer')->render($theme_array, FALSE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('perform node accessibility validation', $account, $return_as_object);
  }

}
