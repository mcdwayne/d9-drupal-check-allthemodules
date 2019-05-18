<?php

namespace Drupal\embedly_filter\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embedly\Embedly;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a text filter to embed URLs using the Embedly service.
 *
 * @Filter(
 *   id = "filter_embedly",
 *   title = @Translation("Embedly Filter"),
 *   description = @Translation("Use [embedly:url] to embed content using Embedly."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterEmbedly extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The Embedly shortcode pattern to match [embedly:http://example.com].
   */
  const EMBEDLY_SHORTCODE_PATTERN = '/\[embedly\:\s?((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)\]/';

  /**
   * The Embedly service.
   *
   * @var \Drupal\embedly\Embedly
   */
  protected $embedly;

  /**
   * Constructs a new FilterEmbedly object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\embedly\Embedly $embedly
   *   The Embedly service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Embedly $embedly) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->embedly = $embedly;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('embedly')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Find shortcode matches.
    preg_match_all(static::EMBEDLY_SHORTCODE_PATTERN, $text, $matches);

    // Return if no matches.
    if (!count($matches)) {
      return new FilterProcessResult($text);
    }

    $to_replace = $matches[0];

    // Request Embedly.
    $data = $this->embedly->oEmbed($matches[1]);

    if ($data) {
      foreach ($data as $index => $result) {
        $output = [
          '#theme' => 'embedly',
          '#data' => $result,
        ];

        // Make text replacements.
        $text = str_replace($to_replace[$index], render($output), $text);
      }
    }

    return new FilterProcessResult($text);
  }

}
