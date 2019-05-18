<?php

namespace Drupal\box\Plugin\Filter;

use Drupal\box\BoxStorage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Class FilterBox
 *
 * Inserts boxes into the content.
 *
 * @package Drupal\box\Plugin\Filter
 *
 * @Filter(
 *   id = "filter_box",
 *   title = @Translation("Insert boxes"),
 *   description = @Translation("Inserts boxes into content using [box:entity_id] tags."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "check_roles" = TRUE
 *   }
 * )
 */
class FilterBox extends FilterBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @var \Drupal\Core\Render\Renderer $renderer
   */
  protected $entityTypeManager;
  protected $renderer;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Render\Renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Renderer $renderer) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Store our dependency.
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (preg_match_all("/\[box:(\d+)(:[^\]]+|)\]/", $text, $match)) {

      // Generate replacements from box:id pattern.
      $raw_tags = $replacements = [];
      foreach ($match[1] as $key => $value) {
        $raw_tags[] = $match[0][$key];
        $box_id = $match[1][$key];
        $view_mode = $match[2][$key];

        if ($box = $this->entityTypeManager->getStorage('box')->load($box_id)) {
          $replacements[] = $this->generateReplacement($box, $view_mode);
        }
      }
      $text = str_replace($raw_tags, $replacements, $text);
    }

    if (preg_match_all("/\[box-name:([a-z0-9_]+)(:[^\]]+|)\]/", $text, $match)) {

      // Generate replacements from box-name:machine_name pattern.
      $raw_tags = $replacements = [];
      foreach ($match[1] as $key => $value) {
        $raw_tags[] = $match[0][$key];
        $box_name = $match[1][$key];
        $view_mode = $match[2][$key];

        if ($box = BoxStorage::loadByMachineName($box_name)) {
          $replacements[] = $this->generateReplacement($box, $view_mode);
        }
      }
      $text = str_replace($raw_tags, $replacements, $text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return t('<a name="filter-box"></a>Please use [box:<em>box_entity_id</em>] and [box:<em>box_entity_id</em>:<em>box_view_mode</em>] tags to display the box.');
  }

  /**
   * Renders box to be used as replacement.
   *
   * @param \Drupal\box\Entity\Box $box
   * @param string $view_mode
   *
   * @return \Drupal\Component\Render\MarkupInterface
   */
  private function generateReplacement($box, $view_mode) {
    if (!$box->access('view')) {
      return '';
    }

    // @todo Check whether view mode exists
    if (!empty($view_mode)) {
      // Remove leading colon.
      $view_mode = substr($view_mode, 1);

      $box_view = $this->entityTypeManager->getViewBuilder('box')->view($box, $view_mode);
    }
    else {
      $box_view = $this->entityTypeManager->getViewBuilder('box')->view($box);
    }
    return $this->renderer->render($box_view);
  }

}
