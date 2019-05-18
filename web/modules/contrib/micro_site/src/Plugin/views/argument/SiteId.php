<?php

namespace Drupal\micro_site\Plugin\views\argument;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for basic site id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("site_id")
 */
class SiteId extends NumericArgument implements ContainerFactoryPluginInterface {

  /**
   * @var EntityStorageInterface
   */
  protected $siteStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $site_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->siteStorage = $site_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('site')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the site.
   */
  public function title() {
    // There might be no valid argument.
    if ($this->argument) {
      $site = $this->siteStorage->load($this->argument);
      if (!empty($site)) {
        return $site->label();
      }
    }
    // TODO review text
    return $this->t('No name');
  }

}
