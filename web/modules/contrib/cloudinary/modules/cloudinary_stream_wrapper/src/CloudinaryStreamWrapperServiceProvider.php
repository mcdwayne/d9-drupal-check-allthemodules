<?php
/**
 * Created by DOOR3 Business Applications, Inc.
 * Developer: Sean Robertson
 * Date: 8/2/16
 * Time: 2:46 PM
 */
namespace Drupal\cloudinary_stream_wrapper;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

class CloudinaryStreamWrapperServiceProvider implements ServiceProviderInterface  {

  public function register(ContainerBuilder $container) {
    if (\Drupal::hasContainer()) {
      $folders = \Drupal::config('cloudinary_sdk.settings')->get('cloudinary_stream_wrapper_folders');
      if (is_array($folders)) {
        $folders = array_filter($folders);
      }
      if (!empty($folders)) {
        foreach ($folders as $folder) {
          //$wrappers['cloudinary.' . $folder] = $base;
          //$wrappers['cloudinary.' . $folder]['name'] .= ' (/' . $folder . ')';

          $container->register('stream_wrapper.cloudinary.' . $folder, 'Drupal\cloudinary_stream_wrapper\StreamWrapper\CloudinaryStreamWrapper')
            ->addTag('stream_wrapper', ['scheme' => $folder]);
        }
      }
    }
  }
}
