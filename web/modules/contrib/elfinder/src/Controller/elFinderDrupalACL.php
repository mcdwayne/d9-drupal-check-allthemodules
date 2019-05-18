<?php

namespace Drupal\elfinder\Controller;

/**
 * @file
 * elfinder ACL class
 */
class elFinderDrupalACL {

  public function fsAccessPublic($attr, $path, $data, $volume) {

    $tmbdir = \Drupal::config('elfinder.settings')->get('thumbnail.dirname');
    $rootpath = drupal_realpath('public://');
  
    if (strpos(basename($path), '.') === 0 && $attr == 'hidden') {
      return TRUE;
    }
    
    /* Hiding thumbnail folder */
    if (!empty($tmbdir) && strstr($path, DIRECTORY_SEPARATOR . $tmbdir) && $attr == 'hidden') {
      return TRUE;
    }

    if (($path == "$rootpath/.quarantine" || $path == "$rootpath/js" || $path == "$rootpath/css" || $path == "$rootpath/php") && $attr == 'hidden') {
      return TRUE;
    }
    
    if (strstr($path, "$rootpath/config_") && $attr == 'hidden') {
      return TRUE;
    }
    
    if ($attr == 'read') {
      return TRUE;
    }

    if ($attr == 'write') {
      return TRUE;
    }

    return FALSE;
  }

  public function fsAccessPrivate($attr, $path, $data, $volume) {
  
  
    $tmbdir = \Drupal::config('elfinder.settings')->get('thumbnail.dirname');

    if (strpos(basename($path), '.') === 0 && $attr == 'hidden') {
      return TRUE;
    }

    /* Hiding thumbnail folder */
    if (!empty($tmbdir) && strstr($path, DIRECTORY_SEPARATOR . $tmbdir) && $attr == 'hidden') {
      return TRUE;
    }

    if (strstr($path, DIRECTORY_SEPARATOR . '.quarantine') && $attr == 'hidden') {
      return TRUE;
    }

    if ($attr == 'read') {
      return TRUE;
    }

    if ($attr == 'write') {
      return TRUE;
    }


    return FALSE;
  }

  public function fsAccessUnmanaged($attr, $path, $data, $volume) {
  
    $tmbdir = \Drupal::config('elfinder.settings')->get('thumbnail.dirname');

    if (strpos(basename($path), '.') === 0 && $attr == 'hidden') {
      return TRUE;
    }

    /* Hiding thumbnail folder */
    if (!empty($tmbdir) && strstr($path, DIRECTORY_SEPARATOR . $tmbdir) && $attr == 'hidden') {
      return TRUE;
    }

    if (strstr($path, DIRECTORY_SEPARATOR . '.quarantine') && $attr == 'hidden') {
      return TRUE;
    }

    if ($attr == 'read') {
      return TRUE;
    }

    return FALSE;
  }

}
