<?php

namespace Drupal\micon\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Component\Serialization\Json;
use Drupal\micon\MiconIcon;

/**
 * Defines the Micon entity.
 *
 * @ConfigEntityType(
 *   id = "micon",
 *   label = @Translation("Micon package"),
 *   label_singular = @Translation("Micon package"),
 *   label_plural = @Translation("Micon packages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Micon package",
 *     plural = "@count Micon packages",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\micon\MiconViewBuilder",
 *     "list_builder" = "Drupal\micon\MiconListBuilder",
 *     "form" = {
 *       "add" = "Drupal\micon\Form\MiconForm",
 *       "edit" = "Drupal\micon\Form\MiconForm",
 *       "delete" = "Drupal\micon\Form\MiconDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\micon\MiconHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "micon",
 *   admin_permission = "administer micon",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/micon/{micon}",
 *     "add-form" = "/admin/structure/micon/add",
 *     "edit-form" = "/admin/structure/micon/{micon}/edit",
 *     "delete-form" = "/admin/structure/micon/{micon}/delete",
 *     "collection" = "/admin/structure/micon"
 *   }
 * )
 */
class Micon extends ConfigEntityBase implements MiconInterface {

  /**
   * The Micon ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Micon label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Micon type. Either 'font' or 'image'. Default is 'font'.
   *
   * @var string
   */
  protected $type = 'font';

  /**
   * The info of this package.
   *
   * @var array
   */
  protected $info = [];

  /**
   * The available icons in this package.
   *
   * @var array
   */
  protected $icons = [];

  /**
   * The folder where Micon packages exist.
   *
   * @var string
   */
  protected $directory = 'public://micon';

  /**
   * {@inheritdoc}
   */
  public function setAsSvg() {
    $this->type = 'image';
  }

  /**
   * {@inheritdoc}
   */
  public function type() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    if (empty($this->info)) {
      $this->info = [];
      $path = $this->getDirectory() . '/selection.json';
      if (file_exists($path)) {
        $data = file_get_contents($path);
        $this->info = Json::decode($data);
      }
    }
    return $this->info;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $info = $this->getInfo();
    return isset($info['metadata']['name']) ? $info['metadata']['name'] : str_replace('-', '', $this->getPrefix());
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    $info = $this->getInfo();
    return $info['preferences'][$this->type() . 'Pref']['prefix'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons() {
    if (empty($this->icons) && $info = $this->getInfo()) {
      $this->icons = [];
      foreach ($info['icons'] as $icon) {
        foreach ($icon['icon']['tags'] as $tag) {
          $icon['tag'] = $tag;
          $icon['prefix'] = $this->getPrefix();
          $icon['directory'] = file_create_url($this->getDirectory());
          $icon['package_id'] = $this->id();
          $icon['package_label'] = $this->label();
          $micon_icon = new MiconIcon($this->type(), $icon);
          $this->icons[$micon_icon->getSelector()] = $micon_icon;
        }
      }
    }
    return $this->icons;
  }

  /**
   * {@inheritdoc}
   */
  public function setArchive($zip_path) {
    $data = strtr(base64_encode(addslashes(gzcompress(serialize(file_get_contents($zip_path)), 9))), '+/=', '-_,');
    $parts = str_split($data, 200000);
    $this->set('archive', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getArchive() {
    $data = implode('', $this->get('archive'));
    return unserialize(gzuncompress(stripslashes(base64_decode(strtr($data, '-_,', '+/=')))));
  }

  /**
   * {@inheritdoc}
   */
  public function getStylesheet() {
    $path = $this->getDirectory() . '/style.css';
    return file_exists($path) ? $path : NULL;
  }

  /**
   * Return the location where Micon packages exist.
   *
   * @return string
   *   The unique path to the package directory.
   */
  protected function getDirectory() {
    return $this->directory . '/' . $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadActive() {
    return Micon::loadMultiple(Micon::loadActiveIds());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadActiveIds() {
    $query = \Drupal::entityQuery('micon')->condition('status', 1);
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadActiveLabels() {
    $labels = [];
    foreach (Micon::loadActive() as $micon) {
      $labels[$micon->id()] = $micon->label();
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!$this->isNew()) {
      $original = $storage->loadUnchanged($this->getOriginalId());
    }

    if (!$this->get('archive')) {
      throw new EntityMalformedException('IcoMoon icon package is required.');
    }
    if ($this->isNew() || $original->get('archive') !== $this->get('archive')) {
      $this->archiveDecode();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    foreach ($entities as $entity) {
      // Remove all files within package directory.
      file_unmanaged_delete_recursive($entity->getDirectory());
      // Clean up empty directory. Will fail silently if it is not empty.
      @rmdir($entity->directory);
    }
  }

  /**
   * Take base64 encoded archive and save it to a temporary file for extraction.
   */
  protected function archiveDecode() {
    $data = $this->getArchive();
    $zip_path = 'temporary://' . $this->id() . '.zip';
    file_put_contents($zip_path, $data);
    $this->archiveExtract($zip_path);
  }

  /**
   * Properly extract and store an IcoMoon zip file.
   *
   * @param string $zip_path
   *   The absolute path to the zip file.
   */
  public function archiveExtract($zip_path) {
    $archiver = archiver_get_archiver($zip_path);
    if (!$archiver) {
      throw new Exception(t('Cannot extract %file, not a valid archive.', ['%file' => $zip_path]));
    }

    $directory = $this->getDirectory();
    file_unmanaged_delete_recursive($directory);
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $archiver->extract($directory);

    // Remove unnecessary files.
    file_unmanaged_delete_recursive($directory . '/demo-files');
    file_unmanaged_delete($directory . '/demo.html');
    file_unmanaged_delete($directory . '/Read Me.txt');

    // Set package type to svg.
    if (file_exists($directory . '/symbol-defs.svg')) {
      $this->setAsSvg();
      // Update symbol to match new id.
      $file_path = $directory . '/symbol-defs.svg';
      $file_contents = file_get_contents($file_path);
      $file_contents = str_replace($this->getPrefix(), $this->id() . '-', $file_contents);
      file_put_contents($file_path, $file_contents);
    }
    else {
      $font_directory = $directory . '/fonts';
      $files_to_rename = $font_directory . '/*.*';
      foreach (glob(drupal_realpath($files_to_rename)) as $file_to_rename_path) {
        $file_new_path = str_replace('fonts/' . $this->getName(), 'fonts/' . $this->id(), $file_to_rename_path);
        if ($file_to_rename_path !== $file_new_path) {

          file_unmanaged_move($file_to_rename_path, $file_new_path, FILE_EXISTS_REPLACE);
        }
      }
    }

    // Update IcoMoon selection.json.
    $file_path = $directory . '/selection.json';
    $file_contents = file_get_contents($file_path);
    // Protect icon keys.
    $file_contents = str_replace('"icons":', 'MICONSIcons', $file_contents);
    $file_contents = str_replace('"icon":', 'MICONIcon', $file_contents);
    $file_contents = str_replace('iconIdx', 'MICONIdx', $file_contents);
    $file_contents = str_replace($this->getPrefix(), 'MICONPrefix', $file_contents);
    // The name and selector should be updated to match entity info.
    $file_contents = str_replace($this->getName(), $this->id(), $file_contents);
    // Return protected keys.
    $file_contents = str_replace('MICONSIcons', '"icons":', $file_contents);
    $file_contents = str_replace('MICONIcon', '"icon":', $file_contents);
    $file_contents = str_replace('MICONIdx', 'iconIdx', $file_contents);
    $file_contents = str_replace('MICONPrefix', $this->id() . '-', $file_contents);
    file_put_contents($file_path, $file_contents);

    // Update IcoMoon stylesheet.
    $file_path = $directory . '/style.css';
    $file_contents = file_get_contents($file_path);
    // The style.css file provided by IcoMoon contains query parameters where it
    // loads in the font files. Drupal CSS aggregation doesn't handle this well
    // so we need to remove it.
    $file_contents = preg_replace('(\?[a-zA-Z0-9#\-\_]*)', '', $file_contents);
    // Protect prefixes.
    $file_contents = str_replace($this->getPrefix(), 'MICON', $file_contents);
    // The name and selector should be updated to match entity info.
    $file_contents = str_replace($this->getName(), $this->id(), $file_contents);
    // Return changed prefixes. This prevents something like m-icon from
    // becoming m-icon-icon.
    $file_contents = str_replace('MICON', $this->id() . '-', $file_contents);
    file_put_contents($file_path, $file_contents);
  }

}
