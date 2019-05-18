<?php

/**
 * @file
 * Contains Drupal\prism\Plugin\Filter\PrismFilter.
 */

namespace Drupal\prism\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to highlight code with the prism.js library.
 *
 * @Filter(
 *   id = "prism_filter",
 *   title = @Translation("Highlight code with prism.js"),
 *   description = @Translation("Highlights code inside [prism:~][/prism:~] tags with the syntax provided at ."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class PrismFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = new FilterProcessResult($text);

    $tags = array();
    if (preg_match_all('/\[prism:([^\|\\]]+)\|?([^\\]]*)?\]/i', $text, $tag_match)) {
      $tags = $tag_match[1];
    }
    if ($tags) {
      foreach (array_unique($tags) as $tag) {
        // Ahhh.
        if (preg_match_all('#((?<!\[)\[)(prism:' . $tag . ')((\s+[^\]]*)*)(\])(.*?)((?<!\[)\[/\2\s*\]|$)#s', $text, $match)) {
          foreach ($match[6] as $value) {
            $replace[] = '<div class="prism-wrapper" rel="' . $tag . '"><pre><code class="language-' . $tag . '">' . htmlspecialchars($value) . '</code></pre></div>';
          }
          foreach ($match[0] as $value) {
            $search[] = $value;
          }
        }
      }

      return str_replace($search, $replace, $text);
    }

    return $text;
  }

}
