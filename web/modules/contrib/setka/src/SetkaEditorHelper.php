<?php

namespace Drupal\setka_editor;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Setka Editor helper service.
 */
class SetkaEditorHelper {

  use StringTranslationTrait;

  const SETKA_EDITOR_TOOLBAR_OFFSET = 80;
  const SETKA_EDITOR_DOMAIN = 'https://editor.setka.io';
  const SETKA_EDITOR_SUPPORT = 'https://editor.setka.io/support';

  /**
   * Drupal messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(MessengerInterface $messenger, ConfigFactory $configFactory) {
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
  }

  /**
   * Checks if setka editor meta data contains.
   *
   * @param array|null $metaData
   *   Setka editor layouts and themes data.
   * @param string $postTheme
   *   Post theme id.
   * @param string $postGrid
   *   Post layout id.
   *
   * @return array
   *   Check results array.
   */
  public function checkPostMeta($metaData, $postTheme, $postGrid) {
    if (!isset($postTheme) && !isset($postGrid)) {
      return ['postTheme' => TRUE, 'postGrid' => TRUE];
    }
    $result = ['postTheme' => FALSE, 'postGrid' => FALSE];
    if ($metaData) {
      if (!is_array($metaData)) {
        $metaData = $this->parseSetkaEditorMeta($metaData);
      }
      if (!empty($metaData['layouts'])) {
        $result['postGrid'] = in_array($postGrid, $metaData['layouts']);
      }
      if (!empty($metaData['themes'])) {
        $result['postTheme'] = in_array($postTheme, $metaData['themes']);
      }
    }

    if (!$result['postTheme'] || !$result['postGrid']) {
      $setkaSupportLink = Link::fromTextAndUrl(
        $this->t('Setka Editor team'),
        Url::fromUri(self::SETKA_EDITOR_SUPPORT, ['attributes' => ['target' => '_blank']])
      )->toString();
      $this->messenger->addError(
        $this->t("Setka Editor can't be launched because Style or Grid System were removed from the Style Manager or you've changed your license key. Please contact @link.", ['@link' => $setkaSupportLink])
      );
    }

    return $result;
  }

  /**
   * Parses Style Manager data to array.
   *
   * @param array|null $currentBuild
   *   Current build data.
   *
   * @return array
   *   Parsed data array.
   */
  public static function parseStyleManagerData($currentBuild) {
    $values = [];
    if (!empty($currentBuild['content_editor_version'])) {
      $values['setka_editor_version'] = $currentBuild['content_editor_version'];
      $values['setka_editor_public_token'] = $currentBuild['public_token'];
      foreach ($currentBuild['content_editor_files'] as $fileData) {
        switch ($fileData['filetype']) {
          case 'js':
            $values['setka_editor_js_cdn'] = $fileData['url'];
            break;

          case 'css':
            $values['setka_editor_css_cdn'] = $fileData['url'];
            break;
        }
      }
      foreach ($currentBuild['theme_files'] as $fileData) {
        switch ($fileData['filetype']) {
          case 'css':
            $values['setka_company_css_cdn'] = $fileData['url'];
            break;

          case 'json':
            $values['setka_company_json_cdn'] = $fileData['url'];
            $values['setka_company_meta_data'] = $fileData['url'];
            break;
        }
      }
      foreach ($currentBuild['plugins'] as $fileData) {
        switch ($fileData['filetype']) {
          case 'js':
            $values['setka_public_js_cdn'] = $fileData['url'];
            break;
        }
      }
    }
    return $values;
  }

  /**
   * Downloads and returns internal file URL.
   *
   * @param string $fileUrl
   *   File CDN URL.
   *
   * @return string
   *   Internal file URL.
   */
  public static function downloadSetkaEditorFile($fileUrl) {
    $fileData = file_get_contents($fileUrl);
    if ($fileData) {
      preg_match('/.*?\/([^\/]+)$/', $fileUrl, $matches);
      if ($matches[1]) {
        $file = file_save_data($fileData, 'public://setka/' . $matches[1], FILE_EXISTS_REPLACE);
        return $file ? file_create_url($file->getFileUri()) : FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Checks if public://setka is writable directory.
   *
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   Drupal file system service.
   *
   * @return bool
   *   Directory is writable - TRUE, else - FALSE
   */
  public static function checkSetkaFolderPermissions(FileSystem $fileSystem) {
    $directory = $fileSystem->realpath("public://setka");
    $is_writable = is_writable($directory);
    $is_directory = is_dir($directory);
    return ($is_writable && $is_directory);
  }

  /**
   * Sets task of URLs to download.
   *
   * @param \Drupal\Core\Config\ImmutableConfig|\Drupal\Core\Config\Config $config
   *   Drupal config object.
   * @param \Drupal\Core\State\State $drupalState
   *   Drupal state service.
   * @param array $newSettings
   *   New Style Manager settings.
   */
  public static function buildSetkaFilesUpdateTask($config, State $drupalState, array $newSettings) {
    $updateTask = $drupalState->get('setka_update_task');
    if (empty($updateTask)) {
      $updateTask = [];
    }

    $styleManagerSettingsRequired = [
      'setka_company_css',
      'setka_company_json',
    ];
    foreach ($styleManagerSettingsRequired as $settingName) {
      $newValue = $newSettings[$settingName . '_cdn'];
      $updateTask[$settingName] = $newValue;
    }
    $styleManagerSettings = [
      'setka_editor_js',
      'setka_editor_css',
      'setka_public_js',
    ];
    foreach ($styleManagerSettings as $settingName) {
      $currentValue = $config->get($settingName . '_cdn');
      $newValue = $newSettings[$settingName . '_cdn'];
      if (!$drupalState->get($settingName) || $currentValue != $newValue) {
        $updateTask[$settingName] = $newValue;
      }
    }
    $drupalState->set('setka_update_task', $updateTask);
  }

  /**
   * This method updates Style Editor files on server storage.
   *
   * @param \Drupal\Core\State\State $drupalState
   *   Drupal state service.
   */
  public static function runSetkaFilesUpdateTask(State $drupalState) {
    $updateTask = $drupalState->get('setka_update_task');
    if (empty($updateTask)) {
      return [];
    }
    $result = [];
    foreach ($updateTask as $configName => $fileUrl) {
      if ($localFileUrl = self::downloadSetkaEditorFile($fileUrl)) {
        $result[$configName] = $localFileUrl;
        unset($updateTask[$configName]);
      }
      else {
        $result[$configName] = FALSE;
        \Drupal::logger('setka_editor')->error('Unable to download Setka Editor file: @url', ['@url' => $fileUrl]);
      }
    }
    if (!empty($updateTask)) {
      $drupalState->set('setka_update_task', $updateTask);
    }
    else {
      $drupalState->delete('setka_update_task');
    }
    $drupalState->setMultiple($result);
  }

  /**
   * Returns max upload file size in bytes or 0 if unlimited.
   *
   * @return int
   *   Max upload file size in bytes.
   */
  public static function getUploadMaxSize() {
    $uploadMaxSize = self::getInBytes(ini_get('post_max_size'));
    $uploadMaxFileSize = self::getInBytes(ini_get('upload_max_filesize'));
    if (!$uploadMaxFileSize || ($uploadMaxSize && $uploadMaxFileSize > $uploadMaxSize)) {
      $uploadMaxFileSize = $uploadMaxSize;
    }
    return (int) $uploadMaxFileSize;
  }

  /**
   * Returns param value in bytes.
   *
   * @param string $val
   *   Php.ini param.
   *
   * @return bool|int
   *   Param value in bytes or FALSE.
   */
  public static function getInBytes($val) {
    $val = trim($val);
    if (empty($val)) {
      return FALSE;
    }
    $last = strtolower($val[strlen($val) - 1]);
    switch ($last) {
      case 'g':
        $val = (int) $val * 1024;
      case 'm':
        $val = (int) $val * 1024;
      case 'k':
        $val = (int) $val * 1024;
    }
    return $val;
  }

  /**
   * Download, parse and save to config Setka Editor meta data.
   *
   * @param string $url
   *   Meta data file URL.
   *
   * @return array
   *   Meta data array.
   */
  public function parseSetkaEditorMeta($url) {
    $setkaCompanyMetaData = ['layouts' => [], 'themes' => []];
    if ($metaDataJson = file_get_contents($url)) {
      $metaData = Json::decode($metaDataJson);
      if (!empty($metaData['assets']['layouts'])) {
        foreach ($metaData['assets']['layouts'] as $layout) {
          $setkaCompanyMetaData['layouts'][] = $layout['id'];
        }
      }
      if (!empty($metaData['assets']['themes'])) {
        foreach ($metaData['assets']['themes'] as $theme) {
          $setkaCompanyMetaData['themes'][] = $theme['id'];
        }
      }
      $this->configFactory->getEditable('setka_editor.settings')
        ->set('setka_company_meta_data', $setkaCompanyMetaData)
        ->save();
    }
    return $setkaCompanyMetaData;
  }

}
