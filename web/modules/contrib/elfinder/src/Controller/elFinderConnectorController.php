<?php

namespace Drupal\elfinder\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use \elFinderConnector;

include_once dirname(elfinder_connector_path()) . DIRECTORY_SEPARATOR . 'elFinderConnector.class.php';
include_once dirname(elfinder_connector_path()) . DIRECTORY_SEPARATOR . 'elFinderVolumeDriver.class.php';
include_once dirname(elfinder_connector_path()) . DIRECTORY_SEPARATOR . 'elFinderVolumeLocalFileSystem.class.php';
include_once $path . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'elfinder.admin.profiles.inc';
use Drupal\elfinder\Controller\elFinderDrupalACL;
include_once 'elFinderDrupal.php';
include_once 'elFinderVolumeDrupal.php';

class elFinderConnectorDrupal extends elFinderConnector {

  public function test() {
    
  }
}

/**
 * Controller routines for taxonomy user routes.
 */
class elFinderConnectorController implements ContainerInjectionInterface {

  public static function create(ContainerInterface $container) {


    return new static(
            $container->get('elfinder.connector')
    );
  }

  /**
   * File browser to filesystem php connector service
   */
  public function getConnector(Request $request) {

    global $base_root;
    
    $user =  \Drupal::currentUser();
    
    $profile = elfinder_get_user_profile();
    
    $path = drupal_get_path('module', 'elfinder');

    $disabled_commands = elfinder_get_disabled_commands();

    $acl = new elFinderDrupalACL();

    $pubFiles = \Drupal::service('stream_wrapper_manager')->getViaUri('public://');
    $pvtFiles = \Drupal::service('stream_wrapper_manager')->getViaUri('private://');

    $roots = array();

    $options_defs = array(
        'disabled' => $disabled_commands, // list of not allowed commands
        'debug' => FALSE,
        'dirSize' => FALSE,
        'tmbSize' => is_object($profile) && $profile->get('thumbnail.size') ? $profile->get('thumbnail.size') : \Drupal::config('elfinder.settings')->get('thumbnail.size'), // thumbnail image size
        'tmbPath' => \Drupal::config('elfinder.settings')->get('thumbnail.dirname'), // thumbnail images directory (tmbPath in 2.x)
        'tmbCrop' => \Drupal::config('elfinder.settings')->get('thumbnail.tmbcrop') == 'true' ? TRUE : FALSE, // crop thumbnail image
        'dateFormat' => 'j M Y H:i', // file modification date format
        'mimeDetect' => is_object($profile) && $profile->get('filesystem.mimedetect') ? $profile->get('filesystem.mimedetect') : \Drupal::config('elfinder.settings')->get('filesystem.mimedetect'), // file type detection method
        'imgLib' => is_object($profile) && $profile->get('thumbnail.imglib') ? $profile->get('thumbnail.imglib') : \Drupal::config('elfinder.settings')->get('thumbnail.imglib'), // image manipulation library
        'fileMode' => is_object($profile) && $profile->get('filesystem.fileperm') ? octdec($profile->get('filesystem.fileperm')) : octdec(\Drupal::config('elfinder.settings')->get('filesystem.fileperm')), // created file permissions
        'dirMode' => is_object($profile) && $profile->get('filesystem.dirperm') ? octdec($profile->get('filesystem.fileperm')) : octdec(\Drupal::config('elfinder.settings')->get('filesystem.dirperm')), // created directory permissions
        //'accessControlData' => array('uid' => $user->id()),
        'acceptedName' => '/^[^\.]+/',
        'uploadMaxSize' => \Drupal::config('elfinder.settings')->get('filesystem.maxfilesize'),
        'userProfile' => $profile,
    );

    
    if (is_object($pubFiles) && \Drupal::currentUser()->hasPermission('access public files')) {

      $roots[] = array_merge($options_defs, array(
          'driver' => 'Drupal',
          'path' => drupal_realpath('public://'), // path to root directory (named 'path' in elFinder 2.0)
          'URL' => $mode == 'relative' ? '/' . $pubFiles->getDirectoryPath() : $pubFiles->getExternalUrl(), // root directory URL
          'alias' => \Drupal::config('elfinder.settings')->get('filesystem.public_root_label') != '' ? \Drupal::config('elfinder.settings')->get('filesystem.public_root_label') : t('Public Files'), // display this instead of root directory name (named 'alias' in elFinder 2.0)
          'accessControl' => array($acl, 'fsAccessPublic'),
      ));
    }
    //return new JsonResponse(array('error' => array(t('CHP0'))));
    if (is_object($pvtFiles) && \Drupal::currentUser()->hasPermission('access private files')) {
      
      $roots[] = array_merge($options_defs, array(
          'driver' => 'Drupal',
          'path' => drupal_realpath('private://'), // path to root directory (named 'path' in elFinder 2.0)
          //'URL' => $mode == 'relative' ? '/' . $pvtFiles->getDirectoryPath() : $pvtFiles->getExternalUrl(), // root directory URL
          'alias' => \Drupal::config('elfinder.settings')->get('filesystem.private_root_label') != '' ? \Drupal::config('elfinder.settings')->get('filesystem.private_root_label') : t('Private Files'), // display this instead of root directory name (named 'alias' in elFinder 2.0)
          'accessControl' => array($acl, 'fsAccessPrivate'),
      ));
    }
    
    if (\Drupal::currentUser()->hasPermission('access unmanaged files')) {
    
      $roots[] = array_merge($options_defs, array(
          'driver' => 'LocalFileSystem',
          'path' => elfinder_file_directory_path(TRUE), // path to root directory (named 'path' in elFinder 2.0)
          //'URL' => elfinder_file_directory_url($mode == 'relative' ? TRUE : FALSE), // root directory URL
          'alias' => \Drupal::config('elfinder.settings')->get('filesystem.unmanaged_root_label') != '' ? \Drupal::config('elfinder.settings')->get('filesystem.unmanaged_root_label') : t('Unmanaged Files'), // display this instead of root directory name (named 'alias' in elFinder 2.0)
          'accessControl' => array($acl, 'fsAccessUnmanaged'),
      ));
    }
    
    if ($profile and $profile->getConf('volumes')) {

      foreach ($profile->getConf('volumes') as $volume) {

        $root = array(
            'alias' => $volume['label'],
        );

        $is_subdir = FALSE;

        $rootpath = '';


        if (isset($volume['path']) && substr($volume['path'], 0, 1) != DIRECTORY_SEPARATOR) {
          $is_subdir = TRUE;
        }

        if ($is_subdir) {

          $root['driver'] = 'Drupal';

          $scheme = file_uri_scheme($volume['path']);

          if ($scheme == FALSE) {
            if (is_object($pvtFiles)) {
              $scheme = 'private';
            } else {
              $scheme = 'public';
            }
          }

          $rootpath = $volume['path'];

          if ($pos = strpos($rootpath, '://')) {
            $rootpath = substr($rootpath, $pos + 3);
          }

          $streamWrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);

          if (is_object($streamWrapper)) {
            $volpath = $streamWrapper->realpath();
            $volurl = $streamWrapper->getExternalUrl();
            $url = isset($volume['url']) && $volume['url'] != '' ? elfinder_parse_path_tokens($volume['url']) : $streamWrapper->getExternalUrl();
          }
          $rootpath = elfinder_parse_path_tokens($rootpath);

          $trimmedpath = ltrim($rootpath, './');

          $rootpath = $volpath . DIRECTORY_SEPARATOR . $trimmedpath;
          $url = rtrim($url, '/');

          $url .= "/$trimmedpath";

          if (!file_prepare_directory($rootpath, FILE_CREATE_DIRECTORY)) {
            drupal_set_message(t('Error. Cannot initialize directory %dir', array('%dir' => $rootpath)), 'error');
          }

          $root['path'] = drupal_realpath($rootpath);
          $root['URL'] = $url;
          $root['tmbPath'] = $volpath . DIRECTORY_SEPARATOR . \Drupal::config('elfinder.settings')->get('thumbnail.dirname');
          $root['tmbURL'] = $volurl . '/' . \Drupal::config('elfinder.settings')->get('thumbnail.dirname');
        } else {

          $rootpath = elfinder_parse_path_tokens($volume['path']);
          $root['driver'] = 'LocalFileSystem';
          $root['path'] = $rootpath;

          if (isset($volume['url'])) {
            $root['URL'] = elfinder_parse_path_tokens($volume['url']);
          }
        }

        $root = array_merge($options_defs, $root);

        $roots[] = $root;
      }
    }
    
    

    $opts = array(
        'roots' => $roots,
		'driver_defaults' => $options_defs,
    );

    $newopts = \Drupal::moduleHandler()->invokeAll('elfinder_connector_config', $opts);

    if ($newopts) {
      $opts = $newopts;
    }

	try {
		$elFinderObj = new elFinderDrupal($opts);
	} catch (Exception $e) {
		\Drupal::moduleHandler()->invokeAll('exit');
		return new JsonResponse(array('error' => array(t('Unable to initialize elFinder object. :msg', array(':msg' => basename($e->getFile()) .": ". $e->getMessage())))));
	}

    $bindcmds = \Drupal::moduleHandler()->invokeAll('elfinder_bind');

    foreach ($bindcmds as $cmd => $cmdfunc) {
      $elFinderObj->bind($cmd, $cmdfunc);
    }

	try {
		$elFinderConnectorObj = new elFinderConnectorDrupal($elFinderObj);
	} catch (Exception $e) {
		\Drupal::moduleHandler()->invokeAll('exit');
		return new JsonResponse(array('error' => array(t('Unable to initialize elFinder connector object. :msg', array(':msg' => basename($e->getFile()) .": ". $e->getMessage())))));
	}

    //\Drupal::moduleHandler()->invokeAll('elfinder_connector_init', $elFinderConnectorObj);
    $token_generator = \Drupal::csrfToken();

    if (!isset($_REQUEST['token']) && !($token_generator->validate($_REQUEST['token']) || 1)) {
      \Drupal::moduleHandler()->invokeAll('exit');
      return new JsonResponse(array('error' => array(t('Access denied'))));
    } else {
      $elFinderConnectorObj->run();
	  try {
		$elFinderConnectorObj->run();
	  } catch (Exception $e) {
		\Drupal::moduleHandler()->invokeAll('exit');
		return new JsonResponse(array('error' => array(t('Unable to run elFinder connector. :msg', array(':msg' => basename($e->getFile()) .": ". $e->getMessage())))));
	  }
    }
    
  }

}
