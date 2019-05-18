<?php

/**
 * @file
 * Hooks provided by the CloudmersiveNsfw module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter whether a file should be scanned by the CloudmersiveNsfw scanner.
 *
 * By default, all files are considered scannable.
 *
 * Examples of use-cases where a scan may not be not wanted include:
 *
 * - Files considered "safe".
 *   Certain file-types have often been considered "safe", for example, it was
 *   thought that image files could not contain a virus (although this has
 *   since been proved incorrect).
 *
 * - Extremely large files.
 *   Invoking the scanner adds overhead, and the larger the file, the longer it
 *   could take to scan. Scanning a large file could cause the Drupal process
 *   to time-out.
 *
 * - Alternative backend storage.
 *   If a file is being saved directly into an alternative backend storage (for
 *   example, a CDN or a cloud file-storage system), it may already be checked
 *   for viruses in the alternative handler, in which case duplicate checking
 *   would be unnecessary.
 *
 * - Deliberate storage of a virus.
 *   Some organisations have a business need to store infected files. Security
 *   researchers, organisations, and many others have a genuine
 *   requirement to allow an infected file to be saved, although they may also
 *   require other parts of the site to be protected.
 *
 * There are many genuine use-cases where virus-scanning should be
 * conditionally disabled for certain files. Modules can choose to require the
 * file is scanned, stop the file from being scanned, or not affect the result.
 *
 * Modules are processed in weight order. The "heaviest" module has the final
 * decision on whether the file is scanned.
 *
 * @param Drupal\file\FileInterface $file
 *   The file to scan.
 *
 * @return int|null
 *   - Drupal\CloudmersiveNsfw\Scanner::FILE_IS_SCANNABLE
 *     File should be scanned.
 *   - Drupal\CloudmersiveNsfw\Scanner::FILE_IS_NOT_SCANNABLE
 *     File should not be scanned.
 *   - Drupal\CloudmersiveNsfw\Scanner::FILE_SCANNABLE_IGNORE
 *     This module does not wish to affect the scannability of this file.
 */
function hook_cloudmersivensfw_file_is_scannable(Drupal\file\FileInterface $file) {
  // Don't scan image files.
  if ($mime_type = $file->getMimeType()) {
    if (strpos($mime_type, '/') && list($classification) = explode('/', $mime_type)) {
      if ($classification == 'image') {
        return Scanner::FILE_IS_NOT_SCANNABLE;
      }
    }
  }

  return Scanner::FILE_SCANNABLE_IGNORE;
}

/**
 * @} End of "addtogroup hooks".
 */
