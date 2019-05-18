<?php

namespace Drupal\filebrowser;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filebrowser\Services\Common;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FilebrowserPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  /**
   * @var Common $common
   */
  protected $common;

  public function __construct(Common $common) {
    $this->common = $common;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('filebrowser.common'));
  }

  public function permissions() {
    return [
      Common::CREATE_LISTING => [
        'title' => $this->t('Create a listing'),
        'description' => $this->t('User is allowed to create a directory listing.'),
      ],
      Common::DELETE_OWN_LISTINGS => [
        'title' => $this->t('Delete listings owned by user'),
        'description' => $this->t('User is allowed to delete a listing created by themselves.'),
      ],
      Common:: DELETE_ANY_LISTINGS => [
        'title' => $this->t('Delete any listing'),
        'description' => $this->t('User is allowed to delete any listing.'),
      ],
      Common::EDIT_OWN_LISTINGS => [
        'title'=> $this->t('Edit listings owned by user'),
        'description' => $this->t('User can edit a listing owned by themselves.'),
      ],
      Common::EDIT_ANY_LISTINGS => [
        'title' => $this->t('Edit any listing'),
        'description' => $this->t('User can edit any listing.'),
      ],
      Common::VIEW_LISTINGS => [
        'title' => $this->t('View listings'),
        'description' => $this->t('User is allowed to view listings.'),
      ],
      Common::FILE_UPLOAD => [
        'title' => $this->t('Upload files'),
        'description' => $this->t('User is allowed to upload files. Node settings must be set to allow uploads.'),
      ],
      Common::DOWNLOAD_ARCHIVE => [
        'title' => $this->t('Download zip archive'),
        'description' => $this->t('Users are allowed to download files as an archive. Node settings must be set to allows downloading an archive.'),
        'restrict access' => false,
      ],
      Common::DELETE_FILES => [
        'title' => $this->t('Delete files'),
        'description' => $this->t('User is allowed to delete files.'),
      ],
      Common::RENAME_FILES => [
        'title' => $this->t('Rename files'),
        'description' => $this->t('User is allowed to rename file names. NOTE: renaming folders is not supported.'),
      ],
      Common::DOWNLOAD => [
        'title' => $this->t('Download files'),
        'description' => $this->t('User is allowed to download files'),
      ],
      Common::CREATE_FOLDER => [
        'title' => $this->t('Create folder'),
        'description' => $this->t('User is allowed to create new directories.'),
      ],
      Common::EDIT_DESCRIPTION => [
        'title' => $this->t('Edit description'),
        'description' => $this->t('User is allowed to edit file descriptions.'),
      ],
    ];
  }

}