<?php

namespace Drupal\dpl\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\dpl\DecoupledPreviewLinks;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dpl\PreviewLinkInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local tasks one for each preview links.
 */
class PreviewLinkLocalTaskDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The consumer preview links.
   *
   * @var \Drupal\dpl\DecoupledPreviewLinks
   */
  protected $decoupledPreviewLinks;

  /**
   * Constructs a new LayoutBuilderLocalTaskDeriver.
   *
   * @param \Drupal\dpl\DecoupledPreviewLinks $decoupled_preview_links
   *   The consumer preview links.
   */
  public function __construct(DecoupledPreviewLinks $decoupled_preview_links) {
    $this->decoupledPreviewLinks = $decoupled_preview_links;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('dpl.preview_links')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (empty($this->derivatives)) {
      $definitions = [];
      foreach ($this->decoupledPreviewLinks->getPreviewLinksInstances() as $preview_link_instance) {
        assert($preview_link_instance instanceof PreviewLinkInstance);
        $definition = $base_plugin_definition;
        $definition['decoupled_preview_link'] = $preview_link_instance->id();
        $definitions[$preview_link_instance->id()] = $definition;
      }
      $this->derivatives = $definitions;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
