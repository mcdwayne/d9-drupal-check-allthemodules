<?php

namespace Drupal\simple_ldap_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_ldap\SimpleLdapServerSchema;
use Drupal\Component\Utility\Unicode;
use Drupal\simple_ldap\SimpleLdapException;
use Drupal\Core\Url;

class SimpleLdapUserSettingsForm extends ConfigFormBase {

  /**
   * @var SimpleLdapServer
   */
  protected $server;

  /**
   * @var SimpleLdapServerSchema
   */
  protected $schema;

  /**
   * {@inheritdoc}
   *
   * Overwrite default constructor so we can also grab the server.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->server = \Drupal::service('simple_ldap.server');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_ldap_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_ldap.user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_ldap.user');

    // Only display the form if we have a successful binding.
    if ($this->server->bind()) {
      $this->schema = \Drupal::service('simple_ldap.schema');
      $object_classes = $this->getObjectClassOptions();
      $schema_defaults = $this->schema->getDefaultAttributeSettings();
      $selected_object_classes = $config->get('object_class') ? $config->get('object_class') : $schema_defaults['object_class'];

      // If this is Active Directory, we lock the main attribute options.
      $readonly = ($this->server->getServerType() == 'Active Directory') ? TRUE : FALSE;
      if ($readonly) {
        drupal_set_message($this->t('Your server is Active Directory, so some settings have been disabled.'), 'warning');
      }

      // If there is user input via an ajax callback, set it here
      $selected_object_classes = $form_state->getValue('object_class') ? $form_state->getValue('object_class') : $selected_object_classes;

      $form['users'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('LDAP Users'),
        '#open' => TRUE,
      );

      $form['users']['basedn'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Base DN'),
        '#description' => $this->t('The Base DN that will be searched for user accounts. Ex: dc=example,dc=com'),
        '#required' => TRUE,
        '#default_value' => $config->get('basedn'),
      );

      $form['users']['user_scope'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Search scope'),
        '#options' => array(
          'sub' => $this->t('Subtree') . ' - ' . t('Search the base DN and all of its children for user accounts.'),
          'one' => $this->t('One-level') . ' - ' . t('Do not include children of the base DN while searching for user accounts.'),
        ),
        '#required' => TRUE,
        '#default_value' => $config->get('user_scope'),
      );

      $form['users']['object_class'] = array(
        '#type' => 'select',
        '#title' => $this->t('User ObjectClass'),
        '#options' => $object_classes,
        '#default_value' => $selected_object_classes,
        '#required' => TRUE,
        '#multiple' => TRUE,
        '#size' => 10,
        '#description' => $this->t('Which LDAP objectClass should be used when searching for a user. This also determines which attributes you have available to map below.'),
        '#ajax' => array(
          'callback' => array($this, 'populateObjectClassAttributes'),
          'wrapper' => 'user-attribute-mapping-wrapper',
        ),
        '#disabled' => $readonly,
      );

      $attributes = $this->getAttributeOptions($selected_object_classes);

      $form['users']['attribute_mapping'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'id' => 'user-attribute-mapping-wrapper',
        ),
      );

      // @TODO Use a Schema object to pull all attributes as options, and use select forms for these attributes
      $form['users']['attribute_mapping']['name_attribute'] = array(
        '#type' => 'select',
        '#title' => t('Username attribute'),
        '#options' => $attributes,
        '#required' => TRUE,
        '#description' => $this->t('Which LDAP attribute should be mapped to a Drupal username. This is commonly "cn" or "uid".'),
        '#default_value' => $config->get('name_attribute') ? $config->get('name_attribute') : $schema_defaults['name_attribute'],
        '#disabled' => $readonly,
      );

      $form['users']['attribute_mapping']['mail_attribute'] = array(
        '#type' => 'select',
        '#title' => t('Mail attribute'),
        '#options' => $attributes,
        '#required' => TRUE,
        '#description' => $this->t('Which LDAP attribute should be mapped to a Drupal email. This is commonly "mail".'),
        '#default_value' => $config->get('mail_attribute') ? $config->get('mail_attribute') : $schema_defaults['mail_attribute'],
        '#disabled' => $readonly,
      );
    }
    else {
      drupal_set_message($this->t('There is a problem with your LDAP Server connection. As a result, this form has been disabled. Please <a href="@url">check your settings</a>.',
        array('@url' => Url::fromRoute('simple_ldap.server')->toString())),
        'warning');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('simple_ldap.user');
    $config
      ->set('basedn', $form_state->getValue('basedn'))
      ->set('user_scope', $form_state->getValue('user_scope'))
      ->set('object_class', $form_state->getValue('object_class'))
      ->set('name_attribute', $form_state->getValue('name_attribute'))
      ->set('mail_attribute', $form_state->getValue('mail_attribute'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * @return array
   *  A array of objectClasses formatted for use as options in a Form API element.
   */
  private function getObjectClassOptions() {
    $object_classes = $this->schema->getSchemaItem('objectClasses');
    foreach ($object_classes as $key => $object_class) {
      $object_classes[$key] = $object_class['name'];
    }

    asort($object_classes);
    return $object_classes;
  }

  /**
   * @param array $object_classes
   *  A list of LDAP objectClasses.
   *
   * @return array
   *  A array of objectClass attributes formatted for use as options in a Form API element.
   */
  private function getAttributeOptions($object_classes) {
    $attributes = array();
    foreach ($object_classes as $object_class) {
      try {
        $result = $this->schema->getAttributesByObjectClass($object_class);
        foreach ($result as $attribute) {
          $attributes[Unicode::strtolower($attribute)] = $attribute;
        }
      }
      catch (SimpleLdapException $e) {
        // Just absorb. No attributes are added to the list.
      }
    }

    asort($attributes);
    return $attributes;
  }

  /**
   * Ajax callback for the object_class element.
   */
  public function populateObjectClassAttributes(array &$form, FormStateInterface $form_state) {
    return array($form['users']['attribute_mapping']);
  }
}
