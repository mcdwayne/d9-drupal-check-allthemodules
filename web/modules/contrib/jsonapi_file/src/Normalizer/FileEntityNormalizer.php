<?php

namespace Drupal\jsonapi_file\Normalizer;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\file_entity\UploadValidatorsTrait;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Normalizer\EntityNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

/**
 * Provides support for files upload.
 */
class FileEntityNormalizer extends EntityNormalizer {

  use UploadValidatorsTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FileEntity::class;

  /**
   * File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FileEntityNormalizer constructor.
   *
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The link manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepository $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Site configs service.
   */
  public function __construct(LinkManager $link_manager, ResourceTypeRepository $resource_type_repository, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    parent::__construct($link_manager, $resource_type_repository, $entity_type_manager);
    $this->fileSystem = $file_system;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {

    /* @var \Drupal\file_entity\Entity\FileEntity $file */
    $file = parent::denormalize($data, $class, $format, $context);

    // If the request does not contain base64 file data, then there are no tasks
    // for the current denormalizer.
    if (empty($data['data'])) {
      return $file;
    }

    // File URI is mandatory field when uploading a new file.
    if (empty($file->getFileUri())) {
      throw new PreconditionRequiredHttpException('Missing the required property "uri".');
    }

    // Make sure the file is going to be stored in on the enabled storage
    // schemes.
    $schemes = file_entity_get_public_and_private_stream_wrapper_names();
    $scheme = $this->fileSystem->uriScheme($file->getFileUri());
    if (!isset($schemes['private'][$scheme]) && !isset($schemes['public'][$scheme])) {
      $message = t('Scheme "@scheme://" is not valid.', ['@scheme' => $scheme]);
      throw new PreconditionFailedHttpException($message);
    }

    // If the current request contains "data" property, we assume that this is
    // base64 encoded file data.
    $file_contents = base64_decode($data['data']);

    // Set the file size ahead of file upload to make this info available
    // during the validation.
    $file->setSize(Unicode::strlen($file_contents));

    // Set the current user as the file owner.
    $account = User::load($this->currentUser->id());
    $file->setOwner($account);

    // Run all necessary validations on the file.
    $this->validateFile($file);

    // Make sure the directory for the file allows file save.
    $dirname = $this->fileSystem->dirname($file->getFileUri());
    file_prepare_directory($dirname, FILE_CREATE_DIRECTORY);

    // Save file content to the destination path.
    $uri = file_unmanaged_save_data($file_contents, $file->getFileUri());
    if (empty($uri)) {
      $error = t('Could not move uploaded file %file to destination %destination.',
        ['%file' => $file->getFilename(), '%destination' => $file->getFileUri()]);

      throw new PreconditionFailedHttpException($error);
    }

    // Set the correct file uri.
    $file->setFileUri($uri);

    // During file save the filename could have changed. So we update it here.
    $filename = $this->fileSystem->basename($uri);
    $file->setFilename($filename);

    return $file;
  }

  /**
   * Validates the current file.
   *
   * @param \Drupal\file_entity\Entity\FileEntity $file
   *   File Entity object.
   */
  protected function validateFile(FileEntity $file) {

    // Get list of allowed file extensions.
    $allowed_extensions = $this->configFactory->get('file_entity.settings')
      ->get('default_allowed_extensions');

    // Get list of available file validators.
    $validators = $this->getUploadValidators(['file_extensions' => $allowed_extensions]);

    // Munge the filename to protect against possible malicious extension
    // hiding within an unknown file type (ie: filename.html.foo).
    if (!empty($allowed_extensions)) {
      $file->setFilename(file_munge_filename($file->getFilename(), $allowed_extensions));
    }

    // Rename potentially executable files, to help prevent exploits (i.e. will
    // rename filename.php.foo and filename.php to filename.php.foo.txt and
    // filename.php.txt, respectively). Don't rename if 'allow_insecure_uploads'
    // evaluates to TRUE.
    $allow_insecure_uploads = $this->configFactory->get('system.file')
      ->get('allow_insecure_uploads');
    $insecure_filename = preg_match('/\.(php|pl|py|cgi|asp|js)(\.|$)/i', $file->getFilename());
    $text_file = substr($file->getFilename(), -4) == '.txt';
    if (!$allow_insecure_uploads && $insecure_filename && !$text_file) {
      $file->setMimeType('text/plain');
      // Force add .txt to the end of file's name and uri to prevent security
      // issues.
      $file->setFilename($file->getFilename() . '.txt');
      $file->setFileUri($file->getFileUri() . '.txt');
      // The .txt extension may not be in the allowed list of extensions. We
      // have to add it here or else the file upload will fail.
      if (!empty($allowed_extensions)) {
        $validators['file_validate_extensions'][0] .= ' txt';
      }
    }

    // Add in our check of the file name length.
    $validators['file_validate_name_length'] = [];

    // Call the validation functions specified by this function's caller.
    $errors = file_validate($file, $validators);

    // Check for validation errors and throw them if there are any.
    if (!empty($errors)) {
      throw new PreconditionFailedHttpException(implode('', $errors));
    }
  }

}
