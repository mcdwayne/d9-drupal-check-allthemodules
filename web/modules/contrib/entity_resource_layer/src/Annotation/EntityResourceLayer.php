<?php

namespace Drupal\entity_resource_layer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for entity resource layer plugin.
 *
 * @Annotation
 */
class EntityResourceLayer extends Plugin {

  /**
   * The entity type ID for which to apply.
   *
   * @var string
   */
  public $entityType;

  /**
   * The entity bundle types for which to apply.
   *
   * In case of null it applies to all.
   *
   * @var string|array|null
   */
  public $bundle = NULL;

  /**
   * Route restrictions.
   *
   * This is useful for having different adaptors for same entity resource
   * providers. For example view listing and the simple CRUD.
   *
   * @var string|array
   */
  public $routes;

  /**
   * If set only these fields will be included.
   *
   * @var array|null
   */
  public $fieldsOnly = NULL;

  /**
   * If set all fields except these will be included.
   *
   * @var array|null
   */
  public $fieldsExcept = NULL;

  /**
   * Field mapping.
   *
   * Original field name => mapped field name.
   *
   * Useful when field names don't represent the actual value stored. Also
   * helps the consumer with shorter and better field names.
   * Fields for which mapping is not provided will remain with default name.
   *
   * @var array
   */
  public $fieldMap = [];

  /**
   * List of entity reference fields whose value should be embedded.
   *
   * @var array
   */
  public $embed = [];

  /**
   * Fields that contain sensitive information.
   *
   * These fields won't be logged upon request.
   *
   * @var array
   */
  public $sensitiveFields = [];

  /**
   * If set the 'field_' prefix of the fields will be replaced with short char.
   *
   * The choice of short char is '$' as it's easy to read and type and as
   * JS developers are already familiar with it.
   *
   * @var bool
   */
  public $trimCustomFields = TRUE;

  /**
   * Whether to format field names to be camelcase.
   *
   * Camel case FTW. I hate underscores.
   *
   * @var bool
   */
  public $camelFields = TRUE;

  /**
   * The focus field.
   *
   * If left empty the object will be returned. If set only this field of the
   * entity will be returned.
   *
   * @var bool
   */
  public $fieldFocus = FALSE;

  /**
   * Execution priority of this adaptor plugin.
   *
   * @var int
   */
  public $priority = 1;

  /**
   * Only execute on given API version.
   *
   * The resource must contain this API version GET parameter so that this
   * adaptor is used. If no GET api version is available it defaults to
   * version 1.
   *
   * The get parameter format is "?_api=1".
   *
   * @var int
   */
  public $apiVersion = 1;

  /**
   * Additional path to defined for the endpoint route.
   *
   * @var null
   */
  public $additionalPath = NULL;

}
