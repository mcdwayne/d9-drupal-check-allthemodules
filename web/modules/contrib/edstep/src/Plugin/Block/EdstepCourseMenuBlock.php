<?php

namespace Drupal\edstep\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\edstep\EdstepService;

/**
 * Provides a 'EdstepCourseMenuBlock' block.
 *
 * @Block(
 *  id = "edstep_course_menu_block",
 *  admin_label = @Translation("EdStep course menu"),
 *  category = @Translation("EdStep"),
 * )
 */
class EdstepCourseMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];
    $edstep_course = \Drupal::request()->attributes->get('edstep_course');
    if(empty($edstep_course)) {
      return [];
    }
    $build['#title'] = $edstep_course->label();
    // $data = array();
    // foreach($course->sections as $section) {
    //   $item = array(
    //     'data' => $section->title_text,
    //   );
    //   foreach($section->activities as $activity) {
    //     $item['children'][] = array(
    //       'data' => l($activity->title_text, 'edstep/course/' . $course->id . '/section/' . $section->id . '/activity/' . $activity->id),
    //       'class' => array('leaf'),
    //     );
    //     $item['class'] = array('expanded');
    //   }
    //   $data[] = $item;
    // }
    $build['content'] = [
      '#theme' => 'edstep_course_menu',
      '#edstep_course' => $edstep_course,
    ];
    $build['#cache']['max-age'] = 0;

    return $build;
  }

}
