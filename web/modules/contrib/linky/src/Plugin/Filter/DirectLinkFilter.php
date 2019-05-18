<?php

namespace Drupal\linky\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert /admin/content/linky/id into the external URL.
 *
 * @Filter(
 *   id = "linky_direct_link_filter",
 *   title = @Translation("Managed Link Direct Link Filter"),
 *   description = @Translation("Converts links to managed link entities to the corresponding external URL."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 10
 * )
 */
class DirectLinkFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Linky storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkyStorage;

  /**
   * Constructs a Drupal\linky\Plugin\Filter\DirectLinkFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linky_storage
   *   The linky entity storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $linky_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkyStorage = $linky_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager')->getStorage('linky'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->processLinkyLinks($text));
  }

  /**
   * Replace a uri like /admin/content/linky/id with the external URL.
   *
   * @param string $text
   *   The HTML from the input widget.
   *
   * @return string
   *   The processed text.
   */
  protected function processLinkyLinks($text) {
    $html_dom = Html::load($text);
    $elements = $html_dom->getElementsByTagName('a');
    foreach ($elements as $element) {
      $val = $element->getAttribute('href');

      if (preg_match('#/admin/content/linky/(?<entity_id>\d+)#', $val, $matches)) {
        /** @var \Drupal\linky\Entity\Linky $linky */
        if ($linky = $this->linkyStorage->load($matches['entity_id'])) {
          $element->setAttribute('href', $linky->link->first()->getUrl()->toString());
        }
      }
    }

    return Html::serialize($html_dom);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Convert managed link entities to use their external URL.');
  }

}
