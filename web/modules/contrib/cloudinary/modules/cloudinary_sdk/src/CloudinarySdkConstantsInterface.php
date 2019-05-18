<?php

namespace Drupal\cloudinary_sdk;

/**
 * Interface CloudinarySdkConstantsInterface.
 */
interface CloudinarySdkConstantsInterface {

  /*
   * Required minimum version number of "CLOUDINARY SDK for PHP".
   */
  const CLOUDINARY_SDK_MINIMUM_VERSION = '1.0.7';

  /**
   * Flag for dealing with "CLOUDINARY SDK for PHP" not loaded.
   */
  const CLOUDINARY_SDK_NOT_LOADED = 0;

  /**
   * Flag for dealing with "CLOUDINARY SDK for PHP" old version.
   */
  const CLOUDINARY_SDK_OLD_VERSION = 1;

  /**
   * Flag for dealing with "CLOUDINARY SDK for PHP" loaded.
   */
  const CLOUDINARY_SDK_LOADED = 2;
}
