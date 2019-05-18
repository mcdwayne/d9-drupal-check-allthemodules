<?php

namespace Drupal\imager\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\imager\Ajax\ImagerCommand;
use Drupal\imager\Popups\ImagerPopups;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImagerController.
 *
 * @package Drupal\imager\Controller
 */
class ImagerController extends ControllerBase {

  /**
   * The Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * ImagerController constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('file_system')
    );
  }

  /**
   * Load a File.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax Response.
   */
  public function loadFile() {
    $data['needamessageorsomething'] = 'nada';
    return (new AjaxResponse())->addCommand(new ImagerCommand($data));
  }

  /**
   * Separate a file path into it's parts.
   *
   * @param string $uri
   *   Path to analyze.
   * @param bool $makeNew
   *   Should we make a temporary path for a new image.
   *
   * @return mixed
   *   Array containing file path parts.
   *
   * @TODO this can probably be replaced with pathinfo().
   */
  private function getFileParts($uri, $makeNew) {
    $fp = pathinfo($uri);

    // Create newfilename - append _1 or increment if already counting.
    $filename = $fp['filename'];
    if (preg_match('/_(\d)$/', $filename, $matches)) {
      $n = $matches[0];
      $filename = preg_replace('/_\d$/', '', $filename);
    }
    else {
      $n = 0;
    }
    if ($makeNew) {
      do {
        $newfilename = $filename . '_' . ++$n . '.' . $fp['extension'];
        $newpath = DRUPAL_ROOT . $fp['dirname'] . '/' . $newfilename;
      } while (file_exists($newpath));

      $fp['newfilename'] = $newfilename;
      $fp['newpath'] = $newpath;
    }

    return $fp;
  }

  /**
   * Write POSTed image to file path.
   *
   * @param string $path
   *   Path to write image to.
   */
  private function writeImage($path, $base64Img) {
    $filtered_data = explode(',', $base64Img);
    $filtered_data[1] = str_replace(' ', '+', $filtered_data[1]);
    if (!$fp = fopen($path, 'w')) {
      $error = error_get_last();
      return 'open_failed: ' . error_get_last();
    }
    if (!fwrite($fp, base64_decode($filtered_data[1]))) {
      return 'write_failed: ' . error_get_last();
    }
    if (!fclose($fp)) {
      return 'close_failed: ' . error_get_last();
    };
  }

  /**
   * Execute ImageMagick convert command to scale image.
   *
   * @param string $spath
   *   Source path for image.
   * @param string $dpath
   *   Destination path for image.
   * @param string $type
   *   If $type == browser then scale to saved configuration settings.
   */
  private function convertImage($spath, $dpath, $type = NULL) {
    switch ($type) {
      case 'browser':
        $geometry = variable_get('imager_browser_width', 1200) . 'x' .
          variable_get('imager_browser_height', 1200);
        $cmd = "/usr/bin/convert -quality 60 -scale $geometry \"$spath\" \"$dpath\" > /tmp/convert.log 2>&1";
        break;

      default:
        $cmd = "/usr/bin/convert -quality 60 \"$spath\" \"$dpath\" > /tmp/convert.log 2>&1";
        break;
    }
    system($cmd);
    if (!file_exists($dpath)) {
      $cmd = "cp $spath $dpath";
      system($cmd);
    }
    $cmd = "rm $spath";
    system($cmd);
  }

  /**
   * Save an edited image into the current media entity or a new one.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function saveFile() {
    $data = json_decode(file_get_contents("php://input"), TRUE);

    // Load the media entity.
    $media = $this->entityTypeManager->getStorage('media')->load($data['mid']);

    $fp = $this->getFileParts(urldecode($data['uri']), TRUE);

    // Save image, process through 'convert' command to reduce file size.
    $tmpPath = file_directory_temp() . '/' . $fp['newfilename'];
    $retcode = $this->writeImage($tmpPath, $data['imgBase64']);
    $this->convertImage($tmpPath, $fp['newpath']);
    $directory = str_replace('sites/default/files/', '', $fp['dirname']);
    $file = File::create(['uri' => 'public://' . $directory . '/' . $fp['newfilename']]);
    $file->save();

    // Initialize some variables.
    $media->get('image')->setValue($this->image);
    $image = $media->get('image')->getValue();
    $thumb = $media->get('thumbnail')->getValue();

    $media = ($data['overwrite'] == "TRUE") ? $media : $media->createDuplicate();

    // Set the primary image.
    $image[0]['target_id'] = $file->id();
    $media->get('image')->setValue(($image));

    // Set the thumbnail.
    $thumb[0]['target_id'] = $file->id();
    $media->get('thumbnail')->setValue($thumb);

    // Set the changed time to current time.
    $media->get('changed')->setValue(REQUEST_TIME);

    // Save the media entity.
    $media->save();

    // @TODO - return confirmation message.
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * Delete a file.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function deleteFile() {
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * Load a map.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadMap() {
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * Load the Image Edit form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadImageForm() {
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * Load a field from the Image Edit form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadImageFieldForm() {
    $response = new AjaxResponse();
    return $response;
  }

  /**
   * List of popups which have been created.
   *
   * @var null
   */
  static private $popups = NULL;

  /**
   * Load one of the Imager dialogs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadDialog() {
    $data = json_decode(file_get_contents("php://input"), TRUE);

    if (empty(self::$popups)) {
      self::$popups = new ImagerPopups();
    }

    $dialog = self::$popups->buildPopup($data);
    $data['content'] = render($dialog['content']);
    if (!empty($dialog['buttonpane'])) {
      $data['buttonpane'] = render($dialog['buttonpane']);
    }
    $data['id'] = $dialog['id'];

    return (new AjaxResponse())->addCommand(new ImagerCommand($data));
  }

  /**
   * Save the image so it can be displayed in a new tab in the browser.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function viewBrowser() {
    $data = json_decode(file_get_contents("php://input"), TRUE);

    $fp = pathinfo(urldecode($data['uri']));

    $tmpDir = $this->fileSystem->realpath('public://imager');
    file_prepare_directory($tmpDir, FILE_CREATE_DIRECTORY);

    $path = $this->fileSystem->tempnam($tmpDir, 'imager_');
    $tmpImagePath = $path . '_tmp.' . $fp['extension'];
    $imagePath    = $path . '.' . $fp['extension'];

    $np = pathinfo($path);

    $data['url'] = $GLOBALS['base_url'] . $GLOBALS['base_path'] .
                   str_replace(DRUPAL_ROOT . '/', '', $tmpDir) . '/' .
                   $np['basename'] . '.' . $fp['extension'];

    $this->writeImage($tmpImagePath, $data['imgBase64']);
    $this->convertImage($tmpImagePath, $imagePath);
    chmod($imagePath, 0744);

    return (new AjaxResponse())->addCommand(new ImagerCommand($data));
  }

  /**
   * Render the current entity using the configured information view_mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function displayEntity() {
    $data = json_decode(file_get_contents("php://input"), TRUE);
    $config = $this->configFactory->get('imager.settings');

    // Load the media entity.
    $media = $this->entityTypeManager->getStorage('media')->load($data['mid']);

    $view_mode = explode('.', $config->get('view_mode_info'))[1];
    $data['html'] = render($this->entityTypeManager->getViewBuilder('media')->view($media, $view_mode));

    return (new AjaxResponse())->addCommand(new ImagerCommand($data));
  }

}
