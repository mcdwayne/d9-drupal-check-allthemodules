<?php

namespace Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators;

use Drupal\kits\Services\KitsInterface;

/**
 * Class ImageExtensionUploadValidatorKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators
 */
class ImageExtensionUploadValidatorKit extends UploadValidatorKit {
  const CALLBACK = 'file_validate_extensions';
  const EXTENSIONS_KEY = 'extensions';
  const EXTENSIONS = [
    'png',
    'gif',
    'jpg',
    'jpeg',
  ];

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::EXTENSIONS_KEY, $context) && NULL !== static::EXTENSIONS) {
      $context[self::EXTENSIONS_KEY] = static::EXTENSIONS;
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function getCallbackArguments() {
    return [
      implode(' ', $this->getExtensions()),
    ];
  }

  /**
   * @param array $default
   *
   * @return array
   */
  public function getExtensions(array $default = []) {
    return $this->getContext(self::EXTENSIONS_KEY, $default);
  }

  /**
   * @param array $extensions
   *
   * @return static
   */
  public function setExtensions(array $extensions = []) {
    return $this->setContext(self::EXTENSIONS_KEY, $extensions);
  }

  /**
   * @param string $ext
   *
   * @return static
   */
  public function appendExtension($ext) {
    $extensions = $this->getExtensions();
    $extensions[] = $ext;
    return $this->setExtensions($extensions);
  }
}
