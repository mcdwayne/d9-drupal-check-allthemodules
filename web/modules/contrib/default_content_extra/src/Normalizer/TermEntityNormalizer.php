<?php

namespace Drupal\default_content_extra\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\default_content\Normalizer\TermEntityNormalizer as TermEntityNormalizerBase;
use Drupal\hal\LinkManager\LinkManagerInterface;

/**
 * Defines a class for normalizing terms.
 */
class TermEntityNormalizer extends TermEntityNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\TermInterface';

  /**
   * A config object for the Default Content Extra configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a TermEntityNormalizer object.
   *
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($link_manager, $entity_manager, $module_handler);
    $this->config = $config_factory->get('default_content_extra.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $normalized = parent::normalize($entity, $format, $context);

    if ($this->config->get('path_alias')) {
      $tid = $entity->id();
      $path = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $tid])->toString();

      // If it's not a system path export it.
      if ($path != "/taxonomy/term/$tid") {
        $normalized['path'] = ['alias' => $path];
      }
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $entity = parent::denormalize($data, $class, $format, $context);

    if ($this->config->get('path_alias')) {
      // Add the path alias if it's included.
      if (!empty($data['path']['alias'])) {
        $entity->path = ['alias' => $data['path']['alias']];
      }
    }

    return $entity;
  }

}
