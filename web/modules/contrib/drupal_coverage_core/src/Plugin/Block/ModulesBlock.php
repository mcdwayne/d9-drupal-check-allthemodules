<?php
/**
 * @file
 * Contains
 */

namespace Drupal\drupal_coverage_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\drupal_coverage_core\AnalysisManager;
use Drupal\drupal_coverage_core\Generator;
use Drupal\drupal_coverage_core\ModuleManager;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Modules' Block
 *
 * @Block(
 *   id = "drupal_coverage_core_module_block",
 *   admin_label = @Translation("DC Modules Overview"),
 * )
 */
class ModulesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var ModuleManager $module_manager */
    $module_manager = \Drupal::service('drupal_coverage_core.module_manager');
    /** @var AnalysisManager $analysis_manager */
    $analysis_manager = \Drupal::service('drupal_coverage_core.analysis_manager');
    $modules = [];

    foreach ($module_manager->getModules() as $mid) {
      $module = Node::load($mid);
      $last_analysis_id = $module_manager->getLastAnalysis($module);

      if ($last_analysis_id !== FALSE) {
        $analysis = Node::load($last_analysis_id);

        $modules[] = [
          'title' => $module_manager->getTitle($module),
          'callout_class' => Generator::getCalloutClass(
            $analysis_manager->getBuildStatus($analysis)
          ),
          'analysis' => [
            'title' => $analysis_manager->getTitle($analysis),
            'duration' => $analysis_manager->getDuration($analysis),
            'finished_at' => $analysis_manager->getFinishedAt($analysis),
            'build_number' => $analysis_manager->getNumber($analysis),
            'build_status' => $analysis_manager->getBuildStatus($analysis),
            'coverage_badge' => $analysis_manager->getCoverageBadge($analysis),
            'finished' => $analysis_manager->isFinished($analysis),
          ],
        ];
      }

    }

    return array(
      '#theme' => 'dc_modules',
      '#markup' => t('hello'),
      '#modules' => $modules,
      '#attached' => array(
        'library' => array(
          'drupal_coverage_core/drupal-coverage-analyses',
        ),
      ),
    );

  }

}
