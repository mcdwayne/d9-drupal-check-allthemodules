<?php


/**
 * @file
 * Contains \Drupal\loopit_krumo\Plugin\Devel\Dumper\KrumoDebug.
 */

namespace Drupal\loopit_krumo\Plugin\Devel\Dumper;


use Drupal\devel\Plugin\Devel\Dumper\DoctrineDebug;
//use Drupal\devel\Plugin\Devel\Dumper\VarDumper;
use Drupal\loopit\Aggregate\AggregateObject;

/**
 * Provides a LoopitDebug dumper plugin.
 *
 * @DevelDumper(
 *   id = "krumo_debug",
 *   label = @Translation("Loopit Krumo Debug"),
 *   description = @Translation("Krumo debug for Loopit.")
 * )
 */
class KrumoDebug extends DoctrineDebug {

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    $name = $name ? $name . ' t=> ' : '';

    // TODO: put in settings
    \krumo::$skin = 'default';
    ob_start();
    krumo($input);
    $dump = ob_get_contents();
    ob_end_clean();

    $dump = $name . $dump;
    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   *
   * Add cast to variables
   */
  public function exportAsRenderable($input, $name = NULL) {

    $output['container'] = [
      '#type' => 'details',
      '#title' => $name ? : $this->t('Variable'),
      '#attached' => [
        'library' => ['devel/devel']
      ],
      '#attributes' => [
        'class' => ['container-inline', 'devel-dumper', 'devel-selectable'],
      ],
      'export' => [
        // Here use caster because not called every where but especially in
        // devel routes.
        '#markup' => $this->export(AggregateObject::castFast($input)),
      ],
    ];

    return $output;
  }

}
