<?php

namespace Drupal\block_theme_sync\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SyncSubscriber.
 *
 * @package Drupal\block_theme_sync
 */
class SyncSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SyncSubcriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManager $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * This method is called whenever the config.save event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onConfigSave(Event $event) {
    static $processed = [];
    $config = $event->getConfig();
    $config_name = $config->getName();

    // Only process a given block once.
    if (!in_array($config_name, $processed) && strpos($config_name, 'block.block.') === 0) {
      $processed[] = $config_name;
      $config_data = $config->getRawData();
      $source_theme = $config_data['theme'];
      $block_storage = $this->entityTypeManager->getStorage('block');
      $source_block_id = $config_data['id'];

      /** @var \Drupal\Core\Entity\Entity\Block $source_block */
      $source_block = $block_storage->load($source_block_id);

      /** @var \Drupal\block_theme_sync\Entity\ThemeMapping[] $theme_mappings */
      $theme_mappings = $this->entityTypeManager->getStorage('theme_mapping')->loadMultiple();

      // Look for mappings for this theme.
      foreach ($theme_mappings as $theme_mapping) {
        if ($theme_mapping->getSource() === $source_theme) {
          $destination_theme = $theme_mapping->getDestination();
          // Determine a suitable destination block ID.
          // @see block_theme_initialize().
          if (strpos($source_block_id, $source_theme . '_') === 0) {
            $destination_block_id = str_replace($source_theme, $destination_theme, $source_block_id);
          }
          else {
            $destination_block_id = $destination_theme . '_' . $source_block_id;
          }
          // Determine if the destination block exists.
          // If so, load it and copy over properties.
          /** @var \Drupal\Core\Entity\Entity\Block $destination_block */
          if ($destination_block = $block_storage->load($destination_block_id)) {
            // Copy over weight.
            $destination_block->setWeight($source_block->getWeight());
            // Copy over visibility settings.
            foreach ($source_block->getVisibility() as $instance_id => $configuration) {
              $destination_block->setVisibilityConfig($instance_id, $configuration);
            }
            // Copy over third-party settings.
            foreach ($source_block->getThirdPartyProviders() as $module) {
              foreach ($source_block->getThirdPartySettings($module) as $key => $value) {
                $destination_block->setThirdPartySetting($module, $key, $value);
              }
            }
          }
          // Otherwise, create a duplicate block.
          else {
            /** @var \Drupal\Core\Entity\Entity\Block $destination_block */
            $destination_block = $source_block->createDuplicateBlock($destination_block_id, $destination_theme);
          }
          // Set the region based on the source to destination mapping.
          foreach ($theme_mapping->getRegionMapping() as $region_mapping) {
            if ($destination_block->getRegion() === $region_mapping['source']) {
              $destination_block->setRegion($region_mapping['destination']);
              break;
            }
          }
          $destination_block->save();
          $this->logger->notice('The block %source_block_id has been synchronized from the %source_theme theme to the %destination_theme theme as %destination_block_id.', [
            '%source_block_id' => $source_block_id,
            '%source_theme' => $source_theme,
            '%destination_theme' => $destination_theme,
            '%destination_block_id' => $destination_block_id,
          ]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];

    return $events;
  }

}
