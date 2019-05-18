<?php

namespace Drupal\edstep\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\edstep\EdstepService;

/**
 * Provides a 'EdstepActivityPagerBlock' block.
 *
 * @Block(
 *  id = "edstep_activity_pager_block",
 *  admin_label = @Translation("EdStep activity pager"),
 *  category = @Translation("EdStep"),
 * )
 */
class EdstepActivityPagerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\edstep\EdstepService definition.
   *
   * @var \Drupal\edstep\EdstepService
   */
  protected $edstep;

  /**
   * Constructs a new EdstepCourseMenuBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EdstepService $edstep
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->edstep = $edstep;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('edstep.edstep')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $edstep_course = \Drupal::request()->attributes->get('edstep_course');
    $section_id = \Drupal::request()->attributes->get('section_id');
    $activity_id = \Drupal::request()->attributes->get('activity_id');
    $build['#title'] = '';
    if(!empty($edstep_course) && !empty($section_id) && !empty($activity_id)) {
      $build['content'] = [
        '#theme' => 'edstep_activity_pager',
        '#course' => $edstep_course,
        //'#course_id' => $edstep_course->get('course_id')->value,
        '#section_id' => $section_id,
        '#activity_id' => $activity_id,
      ];
    }
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
