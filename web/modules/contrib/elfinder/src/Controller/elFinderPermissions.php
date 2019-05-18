<?php

namespace Drupal\elfinder\Controller;

/**
 * @file
 * elfinder ACL class
 */
class elFinderPermissions {

 public function permissions() {
  $perm = array(
    'use file manager' => array(
      'title' => t('Use elFinder file manager'),
      'description' => t('Allow accessing elFinder file manager module'),
    ),

    'administer file manager' => array(
      'title' => t('Administer file manager'),
      'description' => t('Allow users to administer file manager'),
    ),

    'create new directories' => array(
      'title' => t('Create new directories'),
      'description' => t('Allow users to create new directories'),
    ),

    'create new files' => array(
      'title' => t('Create new files'),
      'description' => t('Allow users to create new files'),
    ),

    'rename files and directories' => array(
      'title' => t('Rename files and directories'),
      'description' => t('Allow users to rename files and directories'),
    ),

    'file uploads' => array(
      'title' => t('File uploads'),
      'description' => t('Allow users to upload files'),
    ),

    'paste from clipboard' => array(
      'title' => t('Paste from clipboard'),
      'description' => t('Allow users to paste files from clipboard'),
    ),

    'copy to clipboard' => array(
      'title' => t('Copy to clipboard'),
      'description' => t('Allow users to copy files to clipboard'),
    ),
    
    'cut to clipboard' => array(
      'title' => t('Cut to clipboard'),
      'description' => t('Allow users to cut files to clipboard'),
    ),

    'view file info' => array(
      'title' => t('View file info'),
      'description' => t('Allow users to view file info'),
    ),
    
    'preview files' => array(
      'title' => t('Preview files'),
      'description' => t('Allow users to preview files'),
    ),
    
    'delete files and directories' => array(
      'title' => t('Delete files and directories'),
      'description' => t('Allow users to delete files and directories'),
    ),

    'duplicate files' => array(
      'title' => t('Duplicate files'),
      'description' => t('Allow users to duplicate files'),
    ),

    'edit files' => array(
      'title' => t('Edit files'),
      'description' => t('Allow users to edit files'),
    ),

    'add files to archive' => array(
      'title' => t('Add files to archive'),
      'description' => t('Allow users add files to archive'),
    ),

    'extract files from archive' => array(
      'title' => t('Extract files from archive'),
      'description' => t('Allow users to extract files from archive'),
    ),

    'resize images' => array(
      'title' => t('Resize images'),
      'description' => t('Allow users to resize images'),
    ),

    'download own uploaded files' => array(
      'title' => t('Download own uploaded files'),
      'description' => t('Allow users to download own uploaded files'),
    ),

    'download all uploaded files' => array(
      'title' => t('Download all uploaded files'),
      'description' => t('Allow users to download all uploaded files'),
    ),
    
    'access public files' => array(
      'title' => t('Access public files'),
      'description' => t('Allow users to access public files directory'),
    ),
    
    'access private files' => array(
      'title' => t('Access private files'),
      'description' => t('Allow users to access private files directory'),
    ),

    'access unmanaged files' => array(
      'title' => t('Access unmanaged files'),
      'description' => t('Allow users to access unmanaged files in custom directory'),
    ),
    
    'write public files' => array(
      'title' => t('Write public files'),
      'description' => t('Allow users write access to public files directory'),
    ),
    
    'write private files' => array(
      'title' => t('Write private files'),
      'description' => t('Allow users write access to private files directory'),
    ),

    'access unmanaged files' => array(
      'title' => t('Access unmanaged files'),
      'description' => t('Allow users write access to unmanaged files in custom directory'),
    ),

    'view file description' => array(
      'title' => t('View file descriptions'),
      'description' => t('Allow users to view file descriptions'),
    ),
    
    'edit file description' => array(
      'title' => t('Edit file descriptions'),
      'description' => t('Allow users to edit file descriptions'),
    ),
    
    'view file owner' => array(
      'title' => t('View file owner'),
      'description' => t('Allow users to view file owner'),
    ),
    
    'view file downloads' => array(
      'title' => t('View file downloads'),
      'description' => t('Allow users to view file downloads'),
    ),
      
    'mount network volumes' => array(
      'title' => t('Mount network volumes'),
      'description' => t('Allow users to mount remote network volumes'),
    ),
    
  );
  
  $newperms = \Drupal::moduleHandler()->invokeAll('elfinder_perms', $perm);
  
  return ($newperms) ? $newperms : $perm;
 }


}
