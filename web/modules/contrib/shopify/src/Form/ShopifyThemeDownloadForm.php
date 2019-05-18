<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\shopify\Controller\ShopifyThemeDownload;
use Drupal\shopify\Entity\ShopifyProduct;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class ShopifyThemeDownloadForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifyThemeDownloadForm extends FormBase {

  /**
   * Where the Shopify theme template archive is located.
   */
  const REMOTE_DOWNLOAD_URL = 'https://www.drupal.org/files/default_shopify_theme.zip';

  /**
   * The remote file checksum.
   *
   * Used to validate downloaded copy of the theme archive.
   */
  const REMOTE_DOWNLOAD_SHASUM = 'b920626bd8963783e54e4191fedf7f81cfe896a4';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_theme_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ShopifyProduct $product = NULL) {
    // Check to see if we can ZIP the folder contents.
    $zip_enabled = class_exists('ZipArchive');
    if (!$zip_enabled) {
      drupal_set_message(t('Class <strong>ZipArchive</strong> not found. You will be unable to download or upload the Shopify Theme automatically.<br/>For help with setting up ZipArchive, <a href="@url">view the documentation on php.net</a>.', array(
        '@url' => 'http://php.net/manual/en/zip.setup.php',
      )), 'warning');
    }

    $form['download'] = [
      '#type' => 'details',
      '#title' => t('Shopify Theme'),
      '#description' => t('Download this starter Shopify theme then upload it manually to your <a target="_blank" href="@shopify_store_link">Shopify store</a>, or use the "Upload and Publish Automatically" feature. <span style="color: red">Automatic upload requires this website be publicly accessible from the internet.</span><br /><br />This theme will disable most Shopify store features except for the shopping cart and customer login area, and will redirect the user to your store on your Drupal site if they attempt to access areas covered by this module like products, tags, or collections.<br /><br />We <strong>highly recommend</strong> using this theme generator as a starting point for your Shopify theme. Once uploaded you may use the Shopify theme GUI to match your site\'s colors, fonts, etc.', [
        '@shopify_store_link' => 'https://' . shopify_shop_info('domain') . '/admin/themes',
      ]),
      '#open' => TRUE,
    ];
    $form['download']['actions'] = [
      '#type' => 'actions',
      'download' => [
        '#type' => 'submit',
        '#value' => t('Download Only'),
        '#name' => 'download',
        '#disabled' => !$zip_enabled,
      ],
      'upload' => [
        '#type' => 'submit',
        '#value' => t('Upload and Publish Automatically'),
        '#description' => t('Will be automatically uploaded to your Shopify account and set as the active theme.'),
        '#name' => 'upload',
        '#disabled' => !$zip_enabled,
      ],
    ];

    $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === TRUE ? 'https://' : 'http://';
    $fqdn = $protocol . "$_SERVER[HTTP_HOST]";
    $form['download']['hostname'] = [
      '#type' => 'textfield',
      '#title' => t('Hostname'),
      '#default_value' => $fqdn,
      '#size' => 60,
      '#required' => TRUE,
      '#description' => t('What hostname should the Shopify theme link back to?'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $hostname = $form_state->getValue('hostname');
    if (substr($hostname, -1) != '/') {
      $hostname .= '/';
      $form_state->setValue('hostname', $hostname);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fqdn = $form_state->getValue('hostname');

    // Download from Drupal.org.
    $zip = $this->downloadRemoteCopy();

    if (!$zip) {
      return;
    }

    // Unzip the temp archive so we can modify it.
    $unzipped = $this->unzipArchive($zip);

    if (!$unzipped) {
      return;
    }

    // Modify the {{ replace }} contents within theme files.
    try {
      $url = Url::fromUri($fqdn, ['absolute' => TRUE]);
      $this->findAndReplace($unzipped . '*', '{{ drupal.site.url }}', $url->toUriString());
    }
    catch (\Exception $e) {
      drupal_set_message(t('Could not find and replace placeholder text: @error', ['@error' => $e->getMessage()]), 'error');
    }

    // Modify the {{ replace }} contents within theme files.
    try {
      $url = Url::fromUri($fqdn . shopify_store_url(), ['absolute' => TRUE]);
      $this->findAndReplace($unzipped . '*', '{{ drupal.store.url }}', $url->toUriString());
    }
    catch (\Exception $e) {
      drupal_set_message(t('Could not find and replace placeholder text: @error', ['@error' => $e->getMessage()]), 'error');
    }

    // Zip the theme folder.
    try {
      $zipped = $this->zipFolder($unzipped);
    }
    catch (\Exception $e) {
      drupal_set_message(t('Could not ZIP the default_shopify_theme folder: @error', ['@error' => $e->getMessage()]), 'error');
    }

    switch ($form_state->getTriggeringElement()['#name']) {
      case 'download':
        // Download the file to the user's browser.
        try {
          $download = ShopifyThemeDownload::downloadTheme($zipped);
          $form_state->setResponse($download);
        }
        catch (\Exception $e) {
          drupal_set_message(t('Could not download the ZIP folder: @error', ['@error' => $e->getMessage()]), 'error');
        }
        break;

      case 'upload':
        // Upload the file to Shopify directly.
        try {
          $this->uploadTheme($zipped);
          drupal_set_message(t('Drupal Shopify Theme was uploaded to your store. @link.', [
            '@link' => \Drupal::l(t('View now'), Url::fromUri('https://' . shopify_shop_info('domain') . '/admin/themes', ['attributes' => ['target' => '_blank']])),
          ]));
        }
        catch (\Exception $e) {
          drupal_set_message(t('Could not upload the ZIP folder: @error', ['@error' => $e->getMessage()]), 'error');
        }
        break;
    }
  }

  /**
   * Uploads the theme archive to Shopify.
   *
   * @param string $path
   *   Server path to the theme archive.
   *
   * @throws \Exception
   */
  public static function uploadTheme($path) {
    // Get the timestamp from the folder path.
    $timestamp = substr($path, strpos($path, '/shopify_default_theme_') + 23, 10);
    $config = \Drupal::config('shopify_api.settings');
    $client = shopify_api_client();
    // Create a secure URL.
    $sig = hash_hmac('sha256', $timestamp . 'default_shopify_theme.zip', $config->get('shared_secret'));
    $url = Url::fromRoute('shopify.download_theme', [
      'timestamp' => $timestamp,
      'sig' => $sig,
      'file' => 'default_shopify_theme.zip',
    ], ['absolute' => TRUE]);
    $client->createResource('themes', [
      'theme' => [
        'name' => 'Drupal Shopify Theme',
        'src' => $url->toString(),
        'role' => 'main',
      ],
    ]);
  }

  /**
   * ZIP a folder.
   *
   * @param string $path
   *   Path to the folder.
   *
   * @link http://stackoverflow.com/questions/4914750/how-to-zip-a-whole-folder-using-php @endlink
   *
   * @return string
   *   Zip folder.
   */
  public static function zipFolder($path) {
    // Get real path for our folder.
    $root_path = realpath($path);

    // Initialize archive object.
    $zip = new ZipArchive();
    $zip->open($root_path . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator.
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($root_path),
      RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
      // Skip directories (they would be added automatically)
      if (!$file->isDir()) {
        // Get real and relative path for current file.
        $file_path = $file->getRealPath();
        $relative_path = substr($file_path, strlen($root_path) + 1);

        // Add current file to archive.
        $zip->addFile($file_path, $relative_path);
      }
    }

    // Zip archive will be created only after closing object.
    if ($zip->close()) {
      return $root_path . '.zip';
    }
  }

  /**
   * Find and replace text in a folder recursively.
   *
   * @param string $dir
   *   Directory to search in.
   * @param string $old
   *   Text to find.
   * @param string $new
   *   Text to replace the found text.
   */
  public static function findAndReplace($dir, $old, $new) {
    foreach (glob($dir) as $path_to_file) {
      if (strpos($path_to_file, '.') === FALSE) {
        // This is a directory.
        self::findAndReplace($path_to_file . '/*', $old, $new);
      }
      else {
        $file_contents_old = file_get_contents($path_to_file);
        $file_contents_new = str_replace($old, $new, $file_contents_old);
        if ($file_contents_new !== $file_contents_old) {
          file_put_contents($path_to_file, $file_contents_new);
        }
      }
    }
  }

  /**
   * Unzips an archive.
   *
   * @param string $path
   *   Path of the archive to unzip.
   *
   * @return string
   *   Returns the output directory path.
   */
  public static function unzipArchive($path) {
    // Get real path for our folder.
    $root_path = realpath($path);
    $output_folder = file_directory_temp() . '/shopify_default_theme_' . \Drupal::time()->getRequestTime() . '/';

    // Initialize archive object.
    $zip = new ZipArchive();
    $zip->open($root_path);
    $zip->extractTo($output_folder);
    if ($zip->close()) {
      return $output_folder . 'default_shopify_theme/';
    }
  }

  /**
   * Downloads a copy of the theme template archive.
   *
   * @param string $download_url
   *   Overrides the default download URL from Drupal.org.
   *
   * @return string
   *   Returns the destination file path.
   */
  public static function downloadRemoteCopy($download_url = '') {
    $file_path = system_retrieve_file($download_url ?: self::REMOTE_DOWNLOAD_URL, file_directory_temp() . '/shopify_default_theme_' . \Drupal::time()->getRequestTime() . '.zip', $managed = FALSE, FILE_EXISTS_REPLACE);
    if (sha1_file($file_path) !== self::REMOTE_DOWNLOAD_SHASUM) {
      drupal_set_message(t('Checksum failed. Could not verify the downloaded file. You may need to upgrade this module.'), 'error');
      return FALSE;
    }
    return $file_path;
  }

}
