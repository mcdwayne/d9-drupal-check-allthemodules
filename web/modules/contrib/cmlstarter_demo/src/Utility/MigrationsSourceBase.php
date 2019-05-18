<?php

namespace Drupal\cmlstarter_demo\Utility;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\taxonomy\Entity\Term;

/**
 * Source for Plugins.
 */
class MigrationsSourceBase extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    // SourcePlugin Settings.
    $this->trackChanges = TRUE;

    $uipage = $this->uiPage();
    $config = self::accessProtected($migration, 'pluginDefinition');
    $this->config = $config['process'];
    // Debug switcher.
    $this->debug = FALSE;
    $rows = $this->getRows();
    $this->rows = $rows;
    if ($this->uipage && $this->debug) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        dsm("{$plugin_id}: ProcessMapping & Rows");
        dsm($this->config);
        dsm($rows);
      }
    }
  }

  /**
   * Get Rows.
   */
  public function getRows() {
    $rows = [];
    $this->rows = $rows;
    return $rows;
  }

  /**
   * UiPage.
   */
  public function uiPage() {
    $uipage = FALSE;
    $statuspage = FALSE;
    if (\Drupal::routeMatch()->getRouteName() == "entity.migration.list") {
      $uipage = TRUE;
    }
    if (\Drupal::routeMatch()->getRouteName() == "cmlmigrations.status") {
      $statuspage = TRUE;
    }
    $this->uipage = $uipage;
    $this->statuspage = $statuspage;
    return $uipage;
  }

  /**
   * Access Protected Obj Property.
   */
  public static function accessProtected($obj, $prop) {
    $reflection = new \ReflectionClass($obj);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(TRUE);
    return $property->getValue($obj);
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $rows = $this->getRows();
    return new \ArrayIterator($rows);
  }

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIDs() {
    return [
      'id' => [
        'type' => 'string',
        'alias' => 'id',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('ID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $source = $this->getContent(TRUE);
    return count($source);
  }

  /**
   * Exec.
   */
  public function getContent($dir, $count = FALSE) {
    $module_handler = \Drupal::service('module_handler');
    $path = DRUPAL_ROOT . "/" . $module_handler->getModule('cmlstarter_demo')->getPath();

    $source = [];
    // Scandir.
    $files = scandir("$path/content/{$dir}");
    if (isset($files[2])) {
      foreach ($files as $f) {
        if (substr($f, -4) == '.yml') {
          if (!$count) {
            $source[] = Yaml::parse(file_get_contents("$path/content/{$dir}/$f"));
          }
          else {
            $source[] = $f;
          }
        }
      }
    }
    return $source;
  }

  /**
   * Return terms vocabulary.
   */
  public function getTerms($vid) {
    $result = [];
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->sort('weight', 'ASC');
    $tids = $query->execute();
    if (empty($tids)) {
      return $result;
    }
    if (!empty($tids)) {
      foreach (Term::loadMultiple($tids) as $tid => $term) {
        $id = $term->uuid->value;
        $result[$id]['name'] = $term->name->value;
        $result[$id]['tid'] = $tid;
      }
    }
    return $result;
  }

  /**
   * Return value from vocabulary product_options.
   */
  public function hasProductOptions($term_uuids) {
    $result = [];
    if (is_string($term_uuids)) {
      $term_uuids = [$term_uuids];
    }
    foreach ($term_uuids as $uuid) {
      if (!empty($this->product_options[$uuid])) {
        $tid = $this->product_options[$uuid]['tid'];
        $result[$tid] = ['target_id' => $tid];
      }
    }
    return $result;
  }

  /**
   * Return images.
   */
  public function getFiles($dir = 'cmlstarter-demo', $all = TRUE) {
    $images = [];

    if ($dir || $all) {
      $query = \Drupal::database()->select('file_managed', 'files')
        ->fields('files', [
          'fid',
          'uri',
        ])
        ->condition('uri', "%$dir%", 'LIKE')
        ->condition('status', 1);
      $res = $query->execute();

      if ($res) {
        foreach ($res as $file) {
          $uri = $file->uri;
          $fid = $file->fid;
          $filename = str_replace('public://' . $dir . '/', '', $uri);
          $images[$filename] = $fid;
        }
      }
    }
    return $images;
  }

  /**
   * Ensures the existence of a file.
   */
  public function ensureFiles($filenames, $dir) {
    $file_storage = \Drupal::entityManager()->getStorage('file');
    $raw = "https://raw.githubusercontent.com/synapse-studio/helper/master";
    $tmp = "/tmp";
    $result = [];
    if (is_string($filenames)) {
      $filenames = [$filenames];
    }
    if (is_array($filenames) && !empty($filenames)) {
      $fs = \Drupal::service('file_system');
      $directory = "public://cmlstarter-demo/{$dir}/";
      if (!file_exists($fs->realpath($directory))) {
        \Drupal::service('file_system')->mkdir($directory, NULL, TRUE);
      }
      foreach ($filenames as $fname) {
        $filename = "{$dir}-{$fname}";
        //$files = $file_storage->loadByProperties(['filename' => $filename]);
        //$file = reset($files);
        if (!empty($this->files[$filename])) {
          $fid = $this->files[$filename];
        }
        if (!$this->uiPage() && !$fid) {
          $f = file_get_contents("{$raw}/content/{$dir}/{$fname}");
          $destination = "{$directory}{$filename}";
          $uri = file_unmanaged_save_data($f, $destination, FILE_EXISTS_REPLACE);
          $file = $file_storage->create([
            'filename' => $destination,
            'uri' => $uri,
            'status' => 1,
          ]);
          $file->save();
          $fid = $file->id();
        }
        if (!empty($fid)) {
          $result[$fid] = ['target_id' => $fid];
        }
      }
    }
    return $result;
  }

  /**
   * Return value from vocabulary product_options.
   */
  public function hasProduct($product_uuids) {
    $result = [];
    $file_storage = \Drupal::entityManager()->getStorage('commerce_product');
    if (is_string($product_uuids)) {
      $product_uuids = [$product_uuids];
    }
    if (is_array($product_uuids) && !empty($product_uuids)) {
      foreach ($product_uuids as $uuid) {
        $uuid = "{$uuid}-0000-0000-0000-000000000000";
        $commerce_product = $file_storage->loadByProperties(['uuid' => $uuid]);
        $commerce_product = reset($commerce_product);

        if (is_object($commerce_product)) {
          $id = $commerce_product->id();
          $result[$id] = ['target_id' => $id];
        }
      }
    }
    return $result;
  }

}
