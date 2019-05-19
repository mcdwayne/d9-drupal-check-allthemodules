<?php
namespace Drupal\vde_drush\Plugin\QueueWorker;

/**
 * Plugin for executing queue and writing results to a file.
 *
 * @QueueWorker(
 *   id = "vde_drush_queue",
 *   title = @Translation("Vde drush queue worker"),
 * )
 */
class FileWriter extends FileWriterBase {}
