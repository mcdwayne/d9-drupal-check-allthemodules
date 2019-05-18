<?php

namespace Drupal\regex_redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;

/**
 * The regex redirect entity class.
 *
 * This entity type is based on the redirect entity provided by the contrib
 * redirect module.
 *
 * @ContentEntityType(
 *   id = "regex_redirect",
 *   label = @Translation("Regex Redirect"),
 *   bundle_label = @Translation("Regex Redirect type"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\regex_redirect\Form\RegexRedirectForm",
 *       "delete" = "Drupal\redirect\Form\RedirectDeleteForm",
 *       "edit" = "Drupal\regex_redirect\Form\RegexRedirectForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "\Drupal\regex_redirect\RegexRedirectStorageSchema"
 *   },
 *   base_table = "regex_redirect",
 *   translatable = FALSE,
 *   admin_permission = "administer regex redirects",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "regex_redirect_source",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "language",
 *     "title" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/regex-redirect/edit/{redirect}",
 *     "delete-form" = "/admin/config/search/redirect/delete/{redirect}",
 *     "edit-form" = "/admin/config/search/regex-redirect/edit/{redirect}",
 *   }
 * )
 */
class RegexRedirect extends ContentEntityBase {

  /**
   * Default status code.
   *
   * @var int
   */
  private $defaultStatusCode = 301;

  /**
   * Generates a unique hash for identification purposes.
   *
   * @param string $source_path
   *   Source path of the redirect.
   * @param string $language
   *   Redirect language.
   *
   * @return string
   *   Base 64 hash.
   */
  public static function generateHash($source_path, $language) {
    $hash = [
      'source' => mb_strtolower($source_path),
      'language' => $language,
    ];

    redirect_sort_recursive($hash, 'ksort');
    return Crypt::hashBase64(serialize($hash));
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += [
      'type' => 'regex_redirect',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $this->set('hash', RegexRedirect::generateHash($this->regex_redirect_source->path, $this->language()->getId()));
  }

  /**
   * Sets the regex redirect language.
   *
   * @param string $language
   *   Language code.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
  }

  /**
   * Sets the regex redirect status code.
   *
   * @param int $status_code
   *   The redirect status code.
   */
  public function setStatusCode($status_code) {
    $this->set('status_code', $status_code);
  }

  /**
   * Gets the regex redirect status code.
   *
   * @return int
   *   The redirect status code.
   */
  public function getStatusCode() {
    return $this->get('status_code')->value;
  }

  /**
   * Sets the regex redirect created datetime.
   *
   * @param int $datetime
   *   The redirect created datetime.
   */
  public function setCreated($datetime) {
    $this->set('created', $datetime);
  }

  /**
   * Gets the regex redirect created datetime.
   *
   * @return int
   *   The redirect created datetime.
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * Sets the regex source URL data.
   *
   * @param string $path
   *   The base url of the source.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setSource($path) {
    // Do not set query.
    $this->get('regex_redirect_source')->set(0, [
      'path' => ltrim($path, '/'),
      'query' => '',
    ]);
  }

  /**
   * Gets the regex source URL data.
   *
   * @return array
   *   Regex redirect source.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getSource() {
    return $this->get('regex_redirect_source')->get(0)->getValue();
  }

  /**
   * Gets the source base URL.
   *
   * @return string
   *   Regex redirect source url.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getSourceUrl() {
    return $this->get('regex_redirect_source')->get(0)->getUrl()->toString();
  }

  /**
   * Gets the source URL path without a query.
   *
   * @return string
   *   The source URL path, eventually with its query.
   */
  public function getSourcePathWithQuery() {
    $path = '/' . $this->get('regex_redirect_source')->path;
    return $path;
  }

  /**
   * Gets the redirect URL data.
   *
   * @return array
   *   The redirect URL data.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getRedirect() {
    return $this->get('redirect_redirect')->get(0)->getValue();
  }

  /**
   * Sets the redirect destination URL data without any query.
   *
   * @param string $url
   *   The base url of the redirect destination.
   * @param array $options
   *   The source url options.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function setRedirect($url, array $options = []) {
    $this->get('redirect_redirect')->set(0, [
      'uri' => 'internal:/' . ltrim($url, '/'),
      'options' => $options,
    ]);
  }

  /**
   * Gets the redirect URL.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getRedirectUrl() {
    return $this->get('redirect_redirect')->get(0)->getUrl();
  }

  /**
   * Gets the redirect URL options.
   *
   * @return array
   *   The redirect URL options.
   */
  public function getRedirectOptions() {
    return $this->get('redirect_redirect')->options;
  }

  /**
   * Gets a specific redirect URL option.
   *
   * @param string $key
   *   Option key.
   * @param mixed $default
   *   Default value used in case option does not exist.
   *
   * @return mixed
   *   The option value.
   */
  public function getRedirectOption($key, $default = NULL) {
    $options = $this->getRedirectOptions();
    return isset($options[$key]) ? $options[$key] : $default;
  }

  /**
   * Gets the current regex redirect entity hash.
   *
   * @return string
   *   The hash.
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * Gets the regex redirect title.
   *
   * @return string
   *   The regex redirect title.
   */
  public function getTitle() {
    return $this->get('title')->getValue();
  }

  /**
   * Sets the regex redirect title.
   *
   * @param string $title
   *   The regex redirect title.
   */
  public function setTitle($title) {
    $this->set('title', $title);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Redirect ID'))
      ->setDescription(t('The redirect ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    // The hash field is used to check for duplicates. In the redirect contrib
    // module it is also used to retrieve redirects from the database. This
    // does not work with regex patterns.
    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setSetting('max_length', 64)
      ->setDescription(t('The redirect hash.'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The redirect type.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setDefaultValueCallback('\Drupal\regex_redirect\Entity\RegexRedirect::getCurrentUserId')
      ->setSettings([
        'target_type' => 'user',
      ]);

    // Redirect title for identification of the pages to which this regex
    // should be applied.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The regex redirect title used to identify the url starting points.'));

    // The redirect source should only use internal paths.
    $fields['regex_redirect_source'] = BaseFieldDefinition::create('regex_redirect_source')
      ->setLabel(t('Redirect source'))
      ->setDescription(t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", [
        '%example1' => 'node/123',
        '%example2' => 'taxonomy/term/123',
        '%anchor' => '#anchor',
      ]))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'redirect_link',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // The redirect url should only use internal paths.
    $fields['redirect_redirect'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Redirect target'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // The language should be set to one of the configured languages for each
    // regex redirect.
    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The redirect language.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 2,
      ]);

    $fields['status_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status code'))
      ->setDescription(t('The redirect status code.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date when the redirect was created.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Used to retrieve the default redirect status code.
   *
   * @return int
   *   Status code.
   */
  public function getDefaultStatusCode() {
    return $this->defaultStatusCode;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    // The redirect parameter has to be set for those routes that reuse the
    // redirect module functionality.
    $uri_route_parameters['redirect'] = $uri_route_parameters['regex_redirect'];
    return $uri_route_parameters;
  }

}
