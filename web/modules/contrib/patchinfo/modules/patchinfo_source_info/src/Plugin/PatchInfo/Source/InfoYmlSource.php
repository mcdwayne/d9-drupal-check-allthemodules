<?php

namespace Drupal\patchinfo_source_info\Plugin\patchinfo\source;

use Drupal\Core\Extension\Extension;
use Drupal\patchinfo\PatchInfoSourceBase;

/**
 * Gathers patch information from info.yml files.
 *
 * This source plugin will read patch information from .info.yml files of
 * themes or modules.
 *
 * In the *.info.yml file of a patched theme or module, add a new list like the
 * one shown below:
 * @code
 *
 * patches:
 *   - 'https://www.drupal.org/node/1739718 Issue 1739718, Patch #32'
 *
 * @endcode
 * You can add multiple entries to the list. Each entry should start with the
 * URL of the issue or patch followed by any kind of information about the
 * patch. The URL is optional.
 *
 * You can use any URL or description, that is convenient to you.
 *
 * If you are patching a submodule, you may add the patch entry to the
 * *.info.yml file of the submodule.
 *
 * @PatchInfoSource(
 *   id = "patchinfo_info_yml",
 *   label = @Translation("info.yml", context = "PatchInfoSource"),
 * )
 */
class InfoYmlSource extends PatchInfoSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getPatches(array $info, Extension $file, $type) {
    $return = [];

    if (!in_array($type, ['module', 'theme'])) {
      return $return;
    }

    if (!isset($info['patches']) || !is_array($info['patches']) || count($info['patches']) < 1) {
      return $return;
    }

    foreach ($info['patches'] as $key => $info) {
      // If a colon is used in the patch description and the user didn't enclose
      // the patch entry in single quotes as shown in the README file, the entry
      // is interpreted by the YAML parser as an array in older versions of
      // Drupal. Newer versions of Drupal will fail with a YAML parser exception
      // that we can not reasonably prevent, but to prevent possible database
      // exceptions in older versions of Drupal, we replace the actual
      // information with a warning message instructing the user to check his
      // .info.yml file syntax and log the error as well.
      if (is_array($info)) {
        $this->loggerFactory->get('patchinfo_source_info')->warning($this->t('Malformed patch entry detected in @module.info.yml at index @key. Check the syntax or your info.yml file! In most cases, this may be fixed by enclosing the patch entry in single or double quotes.', [
          '@module' => $file->getName(),
          '@key' => $key,
        ]));
        $info = $this->t('Malformed patch entry detected. Check the syntax of your info.yml file! In most cases, this may be fixed by enclosing the patch entry in single or double quotes.');
      }

      $return[$file->getName()][] = [
        'info' => $info,
        'source' => $file->getPath() . '/' . $file->getName() . '.info.yml',
      ];
    }

    return $return;
  }

}
