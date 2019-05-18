<?php

namespace Drupal\eloqua_app_cloud\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Eloqua AppCloud Interactive Responder
 * as a base for other plugin types.
 *
 * @see \Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudInteractiveResponderBase
 * @see plugin_api
 *
 * @Annotation
 */
class EloquaAppCloudInteractiveResponder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The list of fields required by the plugin.
   *
   * These must be presented in the form:
   * fieldList = {
   *   "EmailAddress" = "{{Contact.Field(C_EmailAddress)}}"
   *  },
   *
   * The key (i.e. EmailAddress), is arbitrary, but must be used consistently and is case sensitive.
   * The value (i.e. Contact.Field(C_EmailAddress)), is the internal Eloqua field description.
   * Extracting this value from Eloqua
   * (possibly by using a call to https://secure.p01.eloqua.com/API/bulk/2.0/contacts/fields )
   * is left as an exercise for the reader.
   *
   * @var array
   *
   */
  public $fieldList;

  /**
   * The API type (contacts or customObject).
   *
   * @var string
   */
  public $api;

  /**
   * The tye of response this plugin expects (either synchronous or asynchronous).
   *
   * @var string
   */
  public $respond;

  /**
   * The description of this plugin. It will be returned to Eloqua on update requests.
   *
   * @var string
   */
  public $description;

  /**
   * If true than Eloqua will require a call to the update endpoint, and the response must indicate
   * requiresConfiguration = FALSE before a canvas can be activated.
   *
   * @TDDO: Implement the logic to make this work.
   *
   * @var boolean
   */
  public $requiresConfiguration;

}
