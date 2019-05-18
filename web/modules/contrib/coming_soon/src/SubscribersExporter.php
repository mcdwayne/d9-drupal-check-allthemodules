<?php

namespace Drupal\coming_soon;

use Drupal\Core\Messenger\MessengerInterface;

/**
 * Manages subscribers export.
 */
class SubscribersExporter {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * MyModuleService constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Main "export" operation. (to be executed by the batch).
   *
   * @param $count
   * @param $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function export($count, &$context) {
    // Start working on a set of results.
    $limit = 50;
    $context['finished'] = 0;
    if (empty($context['sandbox']['offset'])) {
      $context['sandbox']['offset'] = 0;
    }

    $entity_storage = \Drupal::entityTypeManager()
      ->getStorage('coming_soon_subscriber');

    $query = $entity_storage->getQuery()
      ->range(
        $context['sandbox']['offset'],
        $limit
      )->sort('created');
    $nids = $query->execute();
    $nids = array_values($nids);

    // Create the CSV file with the appropriate column headers for this
    // list/network if it hasn't been created yet, and store the file path and
    // field data in the $context for later retrieval.
    if (!isset($context['sandbox']['file'])) {

      // Get field names for this list/network. (I use a helper function here).
      $field_labels = ['ID', 'Email Address', 'Subscription date'];

      // Create the file and print the labels in the header row.
      $filename = 'list_subscribers_export.csv';
      $file_path = file_directory_temp() . '/' . $filename;
      // Create the file.
      $handle = fopen($file_path, 'w');
      // Write the labels to the header row.
      fputcsv($handle, $field_labels);
      fclose($handle);

      // Store file path and subscribers in $context.
      $context['sandbox']['file'] = $file_path;
      $context['sandbox']['subscribers_total'] = $count;

      // Store some values in the results array for processing when finished.
      $context['results']['file'] = $file_path;
    }

    // Open the file for writing ('a' puts pointer at end of file).
    $handle = fopen($context['sandbox']['file'], 'a');

    // Loop until we hit the batch limit.
    for ($i = 0; $i < $limit; $i++) {
      $number_remaining = $context['sandbox']['subscribers_total'];
      $subscriber = $entity_storage->load($nids[$i]);
      if ($number_remaining && isset($nids[$i])) {
        $subscriber_data = [
          $subscriber->id->value,
          $subscriber->email->value,
          date('d-m-Y h:i:s', $subscriber->created->value),
        ];

        fputcsv($handle, $subscriber_data);

        // Increment the counter.
        $context['results']['count'][] = $subscriber->id->value;
        $context['finished'] = count($context['results']['count']) / $context['sandbox']['subscribers_total'];
      }
      // If there are no subscribers remaining, we're finished.
      else {
        $context['finished'] = 1;
        break;
      }
    }

    // Close the file.
    fclose($handle);
    // Increment iteration.
    $context['sandbox']['offset'] += $limit;

    // Show message updating user on how many subscribers have been exported.
    $context['message'] = t('Exported @count of @total subscribers.', [
      '@count' => count($context['results']['count']),
      '@total' => $context['sandbox']['subscribers_total'],
    ]);

  }

  /**
   * Finish exporting (batch) callback.
   *
   * @param $success
   * @param $results
   */
  public function exportFinishedCallback($success, $results) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results['count']),
        'One subscriber exported successfully.', '@count subscribers exported successfully.'
      );
      $this->messenger->addMessage($message, 'status');
    }
    else {
      $message = t('There were errors during the export of this list.');
      $this->messenger->addMessage($message, 'warning');
    }

    // Set some session variables for the redirect to the file download page.
    $_SESSION['csv_download_file'] = $results['file'];
  }

}
