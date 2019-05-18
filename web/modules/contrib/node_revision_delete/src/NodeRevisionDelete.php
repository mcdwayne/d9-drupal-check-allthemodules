<?php

namespace Drupal\node_revision_delete;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;

/**
 * Class NodeRevisionDelete.
 *
 * @package Drupal\node_revision_delete
 */
class NodeRevisionDelete implements NodeRevisionDeleteInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration file name.
   *
   * @var string
   */
  protected $configurationFileName;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, Connection $connection) {
    $this->configurationFileName = 'node_revision_delete.settings';
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function updateTimeMaxNumberConfig($config_name, $max_number) {
    // Getting the config file.
    $config = $this->configFactory->getEditable($this->configurationFileName);
    // Getting the variables with the content types configuration.
    $node_revision_delete_track = $config->get('node_revision_delete_track');
    $changed = FALSE;
    // Checking the when_to_delete value for all the configured content types.
    foreach ($node_revision_delete_track as $content_type => $content_type_info) {
      // If the new defined max_number is smaller than the defined
      // when_to_delete value in the config, we need to change the stored config
      // value.
      if ($max_number < $content_type_info[$config_name]) {
        $node_revision_delete_track[$content_type][$config_name] = $max_number;
        $changed = TRUE;
      }
    }
    // Saving only if we have changes.
    if ($changed) {
      // Saving the values in the config.
      $config->set('node_revision_delete_track', $node_revision_delete_track)->save();
    }

    return $node_revision_delete_track;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeString($config_name, $number) {
    // Geting the config.
    $config_name_time = $this->configFactory->get($this->configurationFileName)->get('node_revision_delete_' . $config_name . '_time');
    // Is singular or plural?
    $time = $this->getTimeNumberString($number, $config_name_time['time']);
    // Return the time string for the $config_name parameter.
    switch ($config_name) {
      case 'minimum_age_to_delete':
        return $number . ' ' . $time;

      case 'when_to_delete':
        return $this->t('After @number @time of inactivity', ['@number' => $number, '@time' => $time]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function saveContentTypeConfig($content_type, $minimum_revisions_to_keep, $minimum_age_to_delete, $when_to_delete) {
    // Getting the config file.
    $config = $this->configFactory->getEditable($this->configurationFileName);
    // Getting the variables with the content types configuration.
    $node_revision_delete_track = $config->get('node_revision_delete_track');
    // Creating the content type info.
    $content_type_info = [
      'minimum_revisions_to_keep' => $minimum_revisions_to_keep,
      'minimum_age_to_delete' => $minimum_age_to_delete,
      'when_to_delete' => $when_to_delete,
    ];
    // Adding the info into te array.
    $node_revision_delete_track[$content_type] = $content_type_info;
    // Saving the values in the config.
    $config->set('node_revision_delete_track', $node_revision_delete_track)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteContentTypeConfig($content_type) {
    // Getting the config file.
    $config = $this->configFactory->getEditable($this->configurationFileName);
    // Getting the variables with the content types configuration.
    $node_revision_delete_track = $config->get('node_revision_delete_track');
    // Checking if the config exists.
    if (isset($node_revision_delete_track[$content_type])) {
      // Deleting the value from the array.
      unset($node_revision_delete_track[$content_type]);
      // Saving the values in the config.
      $config->set('node_revision_delete_track', $node_revision_delete_track)->save();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeValues($index = NULL) {

    $options_node_revision_delete_time = [
      '-1'       => $this->t('Never'),
      '0'        => $this->t('Every time cron runs'),
      '3600'     => $this->t('Every hour'),
      '86400'    => $this->t('Everyday'),
      '604800'   => $this->t('Every week'),
      '864000'   => $this->t('Every 10 days'),
      '1296000'  => $this->t('Every 15 days'),
      '2592000'  => $this->t('Every month'),
      '7776000'  => $this->t('Every 3 months'),
      '15552000' => $this->t('Every 6 months'),
      '31536000' => $this->t('Every year'),
      '63072000' => $this->t('Every 2 years'),
    ];

    if (isset($index) && isset($options_node_revision_delete_time[$index])) {
      return $options_node_revision_delete_time[$index];
    }
    else {
      return $options_node_revision_delete_time;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeNumberString($number, $time) {
    // Time options.
    $time_options = [
      'days' => [
        'singular' => $this->t('day'),
        'plural' => $this->t('days'),
      ],
      'weeks' => [
        'singular' => $this->t('week'),
        'plural' => $this->t('weeks'),
      ],
      'months' => [
        'singular' => $this->t('month'),
        'plural' => $this->t('months'),
      ],
    ];

    return $number == 1 ? $time_options[$time]['singular'] : $time_options[$time]['plural'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCandidatesNodes($content_type, $minimum_revisions_to_keep, $minimum_age_to_delete, $when_to_delete) {
    // Array with sustitution values.
    $array = [
      ':content_type' => $content_type,
      ':revisions_to_keep' => $minimum_revisions_to_keep,
    ];

    if (!$minimum_age_to_delete && !$when_to_delete) {
      $result = $this->connection->query('SELECT n.nid, count(n.nid) as total
                     FROM {node} n
                     INNER JOIN {node_revision} r ON r.nid = n.nid
                     WHERE n.type = :content_type
                     GROUP BY n.nid
                     HAVING count(n.nid) > :revisions_to_keep', $array);

      return $result->fetchCol();
    }

    return [];
  }

}
