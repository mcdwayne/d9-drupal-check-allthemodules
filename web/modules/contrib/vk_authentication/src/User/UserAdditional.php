<?php

namespace Drupal\vk_authentication\User;

use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * Class UserAdditional.
 *
 * @package Drupal\vk_authentication\User
 */
class UserAdditional {

  /**
   * Gets avatar from Vk social network and saves in Drupal user profile.
   *
   * @param \Drupal\user\Entity\User $user
   *   Drupal user.
   * @param string $avatarUri
   *   URI of the users avatar.
   *
   * @return bool
   *   True if succeed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveUserAvatar(User $user, string $avatarUri) {
    $storeDirectory = \Drupal::service('file_system')->realpath('public://vkUsers');
    $storeThumbDirectory = \Drupal::service('file_system')->realpath('public://styles') .
      DIRECTORY_SEPARATOR . 'thumbnail' .
      DIRECTORY_SEPARATOR . 'public' .
      DIRECTORY_SEPARATOR . 'vkUsers';

    $this->checkIfDirectory($storeDirectory);
    $this->checkIfDirectory($storeThumbDirectory);

    $fullPath = $storeDirectory . DIRECTORY_SEPARATOR . basename($avatarUri);
    $fullThumbPath = $storeThumbDirectory . DIRECTORY_SEPARATOR . basename($avatarUri);

    $image = file_get_contents($avatarUri);

    if (!$image) {
      return FALSE;
    }

    file_put_contents($fullPath, $image);
    file_put_contents($fullThumbPath, $image);

    $file = File::create(
      [
        'uid' => $user->id(),
        'filename' => basename($avatarUri),
        'uri' => 'public://vkUsers' . DIRECTORY_SEPARATOR . basename($avatarUri),
        'status' => 1,
      ]
    );

    $file->save();

    $user->set('user_picture', $file->id());
    $user->save();

    return TRUE;
  }

  /**
   * Checking if Drupal profile avatar directory exists.
   *
   * @param string $dir
   *   Directory path.
   */
  private function checkIfDirectory(string $dir) {
    if (!file_exists($dir)) {
      mkdir($dir, 0770, TRUE);
    }
  }

}
