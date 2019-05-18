<?php

namespace Drupal\mailjet_subscription\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Subscription form entity.
 *
 *
 * @ingroup mailjet_subscription
 *
 * @ConfigEntityType(
 *   id = "mailjet_subscription_form",
 *   label = @Translation("Subscription Form"),
 *   admin_permission = "administer subscriptions",
 *   handlers = {
 *     "access" = "Drupal\mailjet_subscription\SubscriptionFormController",
 *     "list_builder" = "Drupal\mailjet_subscription\Controller\SubscriptionFormBuilder",
 *     "form" = {
 *       "add" = "Drupal\mailjet_subscription\Form\SubscriptionFormAddForm",
 *       "edit" = "Drupal\mailjet_subscription\Form\SubscriptionFormEditForm",
 *       "delete" = "Drupal\mailjet_subscription\Form\SubscriptionFormDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/examples/mailjet_subscription/manage/{mailjet_subscription_form}",
 *     "delete-form" = "/examples/mailjet_subscription/manage/{mailjet_subscription_form}/delete"
 *   }
 * )
 */
class SubscriptionForm extends ConfigEntityBase {

  /**
   * The Signup ID.
   *
   * @var int
   */
  public $id;

  /**
   * The Signup Form Machine Name.
   *
   * @var string
   */
  public $name;

  /**
   * The Signup Form Description.
   *
   * @var string
   */
  public $description;

  /**
   * The Signup Form Label of Sumbit button.
   *
   * @var string
   */
  public $sumbit_label;

  /**
   * The Signup Form Destination Page
   *
   * @var string
   */
  public $destination_page;

  /**
   * The Signup Form Confirmation Message
   *
   * @var string
   */
  public $confirmation_message;

  /**
   * The Signup Form Error token
   *
   * @var string
   */
  public $error_token;


  /**
   * The Signup Form Confirmation Rmail Text
   *
   * @var string
   */
  public $confirmation_email_text;


  /**
   * The Signup Form Email Text Button
   *
   * @var string
   */
  public $email_text_button;


  /** The Signup Form Email Text Description
   *
   * @var string
   */
  public $email_text_description;

  /**
   * The Signup Form Email Thank You Message
   *
   * @var string
   */
  public $email_text_thank_you;

  /**
   * The Signup Form Email Owner
   *
   * @var string
   */
  public $email_owner;

  /**
   * The Signup Form Subscribe Error
   *
   * @var string
   */
  public $subscribe_error;

  /**
   * The Signup Form Contact Exist Message
   *
   * @var string
   */
  public $contact_exist;

  /**
   * The Signup Form Success Message Subscribe
   *
   * @var string
   */
  public $success_message_subsribe;

  /**
   * The Signup Form lists
   *
   * @var arrayF
   */
  public $lists;

  /**
   * The Signup Form Conctact propeties
   *
   * @var array
   */
  public $fields_mailjet;

  /**
   * The Signup Form CSS FIELD
   *
   * @var array
   */
  public $css_field;

  /**
   * The Signup Form Js Field
   *
   * @var string
   */
  public $js_field;

  /**
   * The Signup Form Email Footer Text
   *
   * @var string
   */
  public $email_footer_text;

  /**
   * The Signup Form Error Data Type Message
   *
   * @var string
   */
  public $error_data_types;

  /**
   * The Signup Form Sort fields - String
   *
   * @var string
   */
  public $sort_fields;
}
