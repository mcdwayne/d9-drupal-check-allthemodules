<?php

namespace Drupal\file_downloader\Plugin\DownloadOption;

use Drupal\file_downloader\Annotation\DownloadOption;
use Drupal\file_downloader\DownloadOptionPluginBase;

/**
 * Defines a download option plugin.
 *
 * @DownloadOption(
 *   id = "original_file",
 *   label = @Translation("Original File"),
 *   description = @Translation("Download the original file."),
 * )
 */
class OriginalFile extends DownloadOptionPluginBase {

}
