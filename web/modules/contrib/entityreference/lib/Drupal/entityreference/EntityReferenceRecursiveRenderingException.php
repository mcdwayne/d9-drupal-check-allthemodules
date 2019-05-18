<?php

/**
 * @file
 * Definition of Drupal\entityreference\EntityReferenceRecursiveRenderingException.
 */

namespace Drupal\entityreference;

use Exception;

/**
 * Exception thrown when the entity view renderer goes into a potentially infinite loop.
 */
class EntityReferenceRecursiveRenderingException extends Exception {}
