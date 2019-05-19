<?php

namespace Drupal\tmgmt_extension_suit\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\tmgmt_extension_suit\ExtendedTranslatorPluginInterface;

/**
 * Provides a confirmation form for sending multiple content entities.
 */
class DownloadTmgmtActionApproveForm extends BaseTmgmtActionApproveForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tmgmt_extension_suit_download_multiple_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Download Translation');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to download translations for these jobs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Downloading can take some time, do not close the browser');
  }

  /**
   * {@inheritdoc}
   */
  public static function processBatch($data, &$context) {
    $job = parent::processBatch($data, $context);

    if (!empty($job) && $translator = $job->getTranslator()) {
      $plugin = $translator->getPlugin();

      if ($plugin instanceof ExtendedTranslatorPluginInterface &&
        $plugin->downloadTranslation($job)) {
        $context['results']['count']++;
      }
      else {
        $context['results']['errors'][] = new FormattableMarkup('Error downloading %name', [
          '%name' => $job->label(),
        ]);

        return;
      }

      $context['message'] = new FormattableMarkup('Processed %name.', [
        '%name' => $job->label(),
      ]);
    }
    else {
      $context['message'] = new FormattableMarkup('Skipped %name.', [
        '%name' => $data['entity_type'],
      ]);
    }
  }
}
