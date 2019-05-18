<?php

namespace Drupal\colors\Plugin\colors\type;

use Drupal\colors\Plugin\ColorsSchemeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides colors for taxonomy terms.
 *
 * @ColorsScheme(
 *   id = "vocabulary",
 *   module = "colors",
 *   title = "Taxonomy",
 *   label = @Translation("Enable this vocabulary"),
 *   description = @Translation("Colors on a per-taxonomy basis. After enabling a vocabulary, you can set colors for individual taxonomy terms below."),
 *   callback = "\Drupal\colors\Plugin\colors\type\VocabularyScheme::getTerms",
 *   multiple = "\Drupal\taxonomy\Entity\Vocabulary::loadMultiple",
 * )
 */
class VocabularyScheme extends PluginBase implements ColorsSchemeInterface, ContainerFactoryPluginInterface {

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }


  public static function getTerms($vid) {
    $tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vid);
    $terms = array();
    foreach ($tree as $term) {
      $terms[$term->tid] = $term->name;
    }
    return $terms;
  }

}
