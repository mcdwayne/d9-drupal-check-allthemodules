<?php

namespace Drupal\webform_as_block\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides block plugin definitions for mymodule blocks.
 *
 * @see \Drupal\webform_as_block\Plugin\Block\WebformBlock
 */

class WebformBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entity_storage;

  /**
   * Constructs new WebformBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   */
  public function __construct(EntityStorageInterface $entity_storage) {
    $this->entity_storage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorage('webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $config = \Drupal::config('webform_as_block.settings');

    // Get webform IDs from config.
    $webforms = array_values($config->get('webform_list'));
    $webforms = array_filter($webforms);
    $webforms = $this->entity_storage->loadMultiple($webforms);

    foreach ($webforms as $webform) {
      $this->derivatives[$webform->id()] = $base_plugin_definition;
      $this->derivatives[$webform->id()]['admin_label'] = 'Webform: ' . $webform->label();
      $this->derivatives[$webform->id()]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }
}