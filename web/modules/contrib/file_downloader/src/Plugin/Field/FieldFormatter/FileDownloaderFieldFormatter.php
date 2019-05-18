<?php

namespace Drupal\file_downloader\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Form\FormStateInterface;


/**
 *
 * @FieldFormatter(
 *   id = "file_downloader_formatter",
 *   label = @Translation("File Downloader"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileDownloaderFieldFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['download_options'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['download_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Download options'),
      '#options' => $this->getAllDownloadOptions(),
      '#default_value' => $this->getSetting('download_options'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Get a list of options based on configured download options within drupal.
   *
   * @return array
   */
  private function getAllDownloadOptions() {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $downloadOptionConfigStorage = $entityTypeManager->getStorage('download_option_config');
    $downloadOptionConfigEntities = $downloadOptionConfigStorage->loadMultiple();

    $downloadOptions = [];
    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptionConfigEntities */
    foreach ($downloadOptionConfigEntities as $downloadOptionConfig) {
      $label = $downloadOptionConfig->label();
      $extensions = trim($downloadOptionConfig->getExtensions());
      if (empty($extensions)) {
        $label .= ' (' . $downloadOptionConfig->getExtensions() . ')';
      }
      $downloadOptions[$downloadOptionConfig->id()] = $label;
    }

    return $downloadOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $selected_options = array_intersect_key($this->getAllDownloadOptions(), $settings['download_options']);
    $tArgs = ['@text' => implode(" ", $selected_options)];
    $summary[] = $this->t('Download options: @text', $tArgs);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    $entityTypeManager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $downloadOptionConfigStorage */
    $downloadOptionConfigStorage = $entityTypeManager->getStorage('download_option_config');
    $downloadOptionConfigEntities = $downloadOptionConfigStorage->loadMultiple($settings['download_options']);

    /** @var \Drupal\file\FileInterface $file */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $renderArray = $this->getDownloadLinksRenderArray($downloadOptionConfigEntities, $file);

      if (empty($renderArray)) {
        continue;
      }
      $elements[$delta] = $renderArray;
    }

    return $elements;
  }

  /**
   * Get a item list containing download links.
   *
   * @param $downloadOptionConfigEntities
   * @param $file
   *
   * @return mixed
   */
  private function getDownloadLinksRenderArray($downloadOptionConfigEntities, FileInterface $file) {
    $download_links = $this->getDownloadLinks($downloadOptionConfigEntities, $file);

    if (empty($download_links)) {
      return [];
    }

    return [
      '#theme' => 'file_download_list',
      '#content' => [
        '#theme' => 'item_list',
        '#theme_wrappers' => [],
        '#attributes' => [
          'class' => [
            'download-options-list',
          ],
        ],
        '#items' => $download_links,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ],
    ];
  }

  /**
   * Get download links returns <span> with label if the file could not be
   * found.
   *
   * @param $downloadOptionConfigEntities
   * @param \Drupal\file\FileInterface $file
   * @param $download_links
   *
   * @return array
   */
  private function getDownloadLinks($downloadOptionConfigEntities, FileInterface $file) {
    $download_links = [];

    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface $downloadOptionConfig */
    foreach ($downloadOptionConfigEntities as $downloadOptionConfig) {
      $disabled = FALSE;
      $downloadOptionPlugin = $downloadOptionConfig->getPlugin();

      /** @var \Drupal\link\LinkItemInterface $content */
      $url = Url::fromRoute('download_option_config.download_path', [
        'download_option_config' => $downloadOptionConfig->id(),
        'file' => $file->id(),
      ]);

      if (!$url->access()) {
        continue;
      }

      $downloadLink = [
        '#type' => 'link',
        '#title' => $downloadOptionConfig->label(),
        '#url' => $url,
      ];

      $theme = 'file_download_link';
      if (!$downloadOptionPlugin->downloadFileExists($file)) {
        $downloadLink = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $downloadOptionConfig->label(),
        ];

        $theme = 'file_download_disabled';
      }

      $download_links[] = [
        '#theme' => $theme,
        '#content' => $downloadLink,
        '#file' => $file,
        '#downloadOptionConfig' => $downloadOptionConfig,
        '#disabled' => $disabled,
        '#cache' => [
          'contexts' => $file->getCacheContexts() + $downloadOptionConfig->getCacheContexts(),
          'tags' => $file->getCacheTags() + $downloadOptionConfig->getCacheTags(),
        ],
      ];
    }

    return $download_links;
  }

}
