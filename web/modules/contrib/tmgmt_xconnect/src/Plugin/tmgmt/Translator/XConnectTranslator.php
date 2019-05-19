<?php

/**
 * @file
 * Contains \Drupal\tmgmt_xconnect\Plugin\tmgmt\Translator\XConnectTranslator.
 */

namespace Drupal\tmgmt_xconnect\Plugin\tmgmt\Translator;

use Amplexor\XConnect\Request;
use Amplexor\XConnect\Request\File\ZipFile;
use Amplexor\XConnect\Service\FtpService;
use Amplexor\XConnect\Service\SFtpService;
use Drupal\Component\Utility\Xss;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\Translator\TranslatableResult;

/**
 * AMPLEXOR X-Connect translator plugin.
 *
 * @link http://goo.gl/xCQ4em Translator plugin for the AMPLEXOR Translation Services.@endlink
 * @link https://github.com/amplexor-drupal/xconnect We are using the AMPLEXOR X-Connect PHP client.@endlink
 *
 * @TranslatorPlugin(
 *   id = "xconnect",
 *   label = @Translation("X-Connect translator"),
 *   description = @Translation("X-Connect Translator service."),
 *   ui = "Drupal\tmgmt_xconnect\XConnectTranslatorUi",
 *   logo = "icons/AMPLEXOR.png",
 * )
 */
class XConnectTranslator extends TranslatorPluginBase implements ContinuousTranslatorInterface {

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('order') && $translator->getSetting('connection')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->url(),
     ]));
  }

  /**
   * {@inheritdoc}
   */
  public function checkTranslatable(TranslatorInterface $translator, JobInterface $job) {
    // Anything can be exported.
    return TranslatableResult::yes();
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('The translation job has been sent to GCM.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasCheckoutSettings(JobInterface $job) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return array(
      'order' => array(
        'client_id' => '',
        'due_date' => 0,
        'issued_by' => '',
        'is_confidential' => 0,
        'needs_confirmation' => 1,
        'needs_quotation' => 0,
      ),
      'connection' => array(
        'protocol' => 'FTP',
        'port' => '21',
        'timeout' => '90',
        'hostname' => '',
        'username' => '',
        'password' => '',
        'directory_send' => 'To_LSP',
        'directory_send_processed' => 'To_LSP_processed',
        'directory_receive' => 'From_LSP',
        'directory_receive_processed' => 'From_LSP_processed',
      ),
      'cron' => array(
        'status' => 1,
        'limit' => 0,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = reset($job_items)->getJob();

    // Order name (file name) based on the Job details.
    $order_name = sprintf(
      'JobID%s_%s_%s',
      $job->id(),
      $job->getSourceLangcode(),
      $job->getTargetLangcode()
    );

    // Create a new translation request.
    $settings_order = $job->getSetting('order');
    $request = new Request(
      $job->getSourceLangcode(),
      [
        'clientId'          => $settings_order['client_id'],
        'orderNamePrefix'   => 'drupal8_order',
        'dueDate'           => $job->getSetting('due_date'),
        'issuedBy'          => $job->getSetting('issued_by'),
        'isConfidential'    => $settings_order['is_confidential'],
        'needsConfirmation' => $settings_order['needs_confirmation'],
        'needsQuotation'    => $settings_order['needs_quotation'],
      ]
    );

    // Fill in the request details:
    // The Language(s) to translate the content to:
    $request->addTargetLanguage($job->getRemoteTargetLanguage());

    // Optional instructions and reference:
    if (!empty($job->getSetting('instructions'))) {
      $request->addInstruction(Xss::filter($job->getSetting('instructions')));
    }
    if (!empty($job->getSetting('reference'))) {
      $request->setReference(Xss::filter($job->getSetting('reference')));
    }

    // Add the translation strings, add a separate file for each Job Item.
    $export = \Drupal::service('plugin.manager.tmgmt_xconnect.format')->createInstance('html');
    /** @var \Drupal\tmgmt\Entity\JobItem $job_item */
    foreach ($job_items as $job_item) {
      if ($job->isContinuous()) {
        $job_item->active();
      }
      $request->addContent(
        $order_name . '_' . $job_item->id() . '.html',
        $export->exportJobItem($job_item)
      );
    }

    // Create a service object by passing the connection details.
    $settings_connection = $job->getSetting('connection');
    $settings_debug = $job->getSetting('debug');
    $settings_connection['debug'] = $settings_debug['status'];
    if ($settings_connection['protocol'] === 'SFTP') {
      // Transport over SSH (encryption).
      $service = new SFtpService($settings_connection);
    }
    else {
      // Transport over FTP (no encryption).
      $service = new FtpService($settings_connection);
    }

    // Send the request as a zip file.
    $result = $service->send(
      ZipFile::create(
        $request,
        tmgmt_xconnect_directory_request()
      )
    );
  }

}
