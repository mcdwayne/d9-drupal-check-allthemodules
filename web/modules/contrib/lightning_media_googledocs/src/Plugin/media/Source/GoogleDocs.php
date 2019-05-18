<?php

namespace Drupal\lightning_media_googledocs\Plugin\media\Source;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_googledocs\Plugin\media\Source\GoogleDocs as BaseGoogleDocs;

/**
 * Input-matching version of the Google document media type.
 */
class GoogleDocs extends BaseGoogleDocs implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
