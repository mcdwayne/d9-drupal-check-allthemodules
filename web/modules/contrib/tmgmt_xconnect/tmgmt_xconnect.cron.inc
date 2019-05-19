<?php
/**
 * @file
 * Contains the functionality to run the translations receive batch during cron.
 */

use \Drupal\tmgmt\TranslatorInterface;

/**
 * Run cron for a given translator.
 *
 * @param \Drupal\tmgmt\TranslatorInterface $translator
 *   The translator service.
 */
function tmgmt_xconnect_cron_receive(TranslatorInterface $translator) {
  // Get cron settings.
  $config = $translator->getSetting('cron');

  // Make sure cron is enabled for this translator.
  if (!$config['status']) {
    \Drupal::logger(TMGMT_XCONNECT_WATCHDOG)->notice('Cron disabled for translator %translator', [
      '%translator' => $translator->label(),
    ]);
    return;
  }

  // Check limit.
  $limit = (!empty($config['limit']))
    ? (int) $config['limit']
    : 1000000;

  try {
    // Get the files ready for translation.
    $service = tmgmt_xconnect_connection($translator);
    $files = $service->scan();
  }
  catch (Exception $e) {
    \Drupal::logger(TMGMT_XCONNECT_WATCHDOG)->error('Cron : Could not scan for translated jobs for %translator : %message.', [
      '%translator' => $translator->label(),
      '%message' => $e->getMessage(),
    ]);
    return;
  }

  // Log to watchdog if there are no files to process.
  if (!$files) {
    \Drupal::logger(TMGMT_XCONNECT_WATCHDOG)->notice('Cron : No translations to import for translator %translator.', [
      '%translator' => $translator->label(),
    ]);
    return;
  }

  // Process the files that are ready.
  $count_files = 0;
  foreach ($files as $filename) {
    // Check the limit to avoid cron time-out.
    if ($count_files >= $limit) {
      \Drupal::logger(TMGMT_XCONNECT_WATCHDOG)->notice('Cron : File cron limit reached, processed %count of %count_total files for translator %translator.', [
        '%translator' => $translator->label(),
        '%count' => $count_files,
        '%count_total' => count($files),
      ]);
      break;
    }

    try {
      // Process the file.
      tmgmt_xconnect_import_remote_file($translator, $filename);
    }
    catch (ImportException $e) {
      \Drupal::logger(TMGMT_XCONNECT_WATCHDOG)->error('Could not process file %filename : %message', [
        '%filename' => $filename,
        '%message' => $e->getMessage(),
      ]);
    }

    $count_files++;
  }
}
