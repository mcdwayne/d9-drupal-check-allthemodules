<?php

namespace Drupal\applenews\Commands;

use Drupal\applenews\Entity\ApplenewsChannel;
use Drupal\Core\Site\Settings;
use Drush\Commands\DrushCommands;

/**
 * Class ApplenewsCommands.
 *
 * @package Drupal\applenews\Commands
 */
class ApplenewsCommands extends DrushCommands {
  /**
   * The settings instance.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * ChannelImporter constructor.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * Updates channel information from Apple News.
   *
   * @usage applenews-update_channel
   *   Update channel meta data from Apple News.
   *
   * @command applenews:update_channel
   * @aliases applenewsuc
   */
  public function updateChannel() {
    if ($channel_id = $this->settings->get('applenews_channel_id')) {
      $this->logger()->info(dt('Found default channel ID: ' . $this->getProcessedString($channel_id)));

      $channel = ApplenewsChannel::loadByChannelId($channel_id);

      if ($channel) {
        $this->logger()->info(dt('Channel entity exist. Refreshing data...'));

        try {
          $channel->updateMetaData();
        }
        catch (\Exception $e) {
          $this->logger()->error(dt('Error while fetching details.'));
          return;
        }
      }
      else {
        $this->logger()->info(dt('Creating new channel entity...'));

        try {
          ApplenewsChannel::create(['id' => $channel_id])->updateMetaData();
        }
        catch (\Exception $e) {
          $this->logger()->error(dt('Error while fetching details.'));
          return;
        }
      }
      $this->logger()->success(dt('Updated channel details successfully.'));
    }
    else {
      $this->logger()->error(dt('Channel ID is missing'));
    }
  }

  /**
   * Gets channel ID to printable format.
   *
   * Prints in ABCD****-****-****-****-********WXYZ format.
   *
   * @param string $string
   *   Input string to process.
   * @param int $start
   *   Starting position to hide.
   * @param int $end
   *   Starting position to hide.
   * @param string $replace_string
   *   String replacement.
   * @param array $exclude_chars
   *   String replacement.
   *
   * @return string
   *   Printable string.
   */
  protected function getProcessedString($string, $start = 4, $end = 4, $replace_string = '*', array $exclude_chars = ['-']) {
    if ($string) {
      for ($i = $start; $i < strlen($string) - $end; $i++) {
        if (!in_array($string[$i], $exclude_chars)) {
          $string[$i] = $replace_string;
        }
      }
    }
    return $string;
  }

}
