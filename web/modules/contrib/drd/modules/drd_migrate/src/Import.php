<?php

namespace Drupal\drd_migrate;

use Drupal\Component\Serialization\Json;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\user\Entity\User;
use Drupal\drd\Entity\Domain;
use Drupal\drd\Entity\Host;

/**
 * Class Import.
 *
 * @package Drupal\drd
 */
class Import {

  /**
   * Manage output to console depending on context.
   *
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The IO object which might be NULL.
   * @param string $text
   *   The text to be output.
   * @param bool $error
   *   Indicate if this is an error message or not.
   */
  private function output(DrupalStyle $io, $text, $error = FALSE) {
    if (isset($io)) {
      if ($error) {
        $io->error($text);
      }
      else {
        $io->info($text);
      }
    }
    else {
      if ($error) {
        drush_set_error($text);
      }
      else {
        /* @noinspection PhpDeprecationInspection */
        drush_print($text);
      }
    }
  }

  /**
   * Execute the import command.
   *
   * @param string $filename
   *   The full path and filename which holds the inventory for import.
   * @param \Drupal\Console\Core\Style\DrupalStyle $io
   *   The IO object from Drush or Console for output.
   */
  public function execute($filename, DrupalStyle $io = NULL) {
    if (!file_exists($filename)) {
      $this->output($io, 'Inventory file does not exist!', TRUE);
      return;
    }
    try {
      $inventory = Json::decode(file_get_contents($filename));
    }
    catch (\Exception $ex) {
      $this->output($io, 'Inventory file can not be read!', TRUE);
      return;
    }
    \Drupal::currentUser()->setAccount(User::load(1));
    $storage = \Drupal::entityTypeManager()->getStorage('drd_core');

    foreach ($inventory as $id => $coredomains) {
      $this->output($io, 'Import core ' . $id);
      /** @var \Drupal\drd\Entity\Core $core */
      $core = $storage->create([
        'name' => 'Migrate ' . $id,
      ]);
      foreach ($coredomains as $coredomain) {
        $url = $coredomain['ssl'] ? 'https://' : 'http://';
        $url .= $coredomain['url'];
        $this->output($io, '  Import ' . $url);
        $domain = Domain::instanceFromUrl($core, $url, []);
        if ($domain->isNew()) {
          $domain->initValues($coredomain['url']);
        }
        else {
          $core = $domain->getCore();
        }
        if ($domain->pushOTT($coredomain['token'])) {
          if ($core->isNew()) {
            // Try to find the correct host or create a new one.
            $host = Host::findOrCreateByHost(parse_url($url, PHP_URL_HOST));
            $core->setHost($host);
            if (!$domain->initCore($core)) {
              continue;
            }
          }
          $domain->set('installed', 1);
          $domain->setCore($core);
          $domain->save();
          $core = $domain->getCore();
        }
      }
    }
  }

}
