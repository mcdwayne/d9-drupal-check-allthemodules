<?php

namespace Drupal\ext_redirect\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\ext_redirect\RedirectRuleHelper;
use Drupal\user\UserInterface;

/**
 * Defines the Redirect Rule entity.
 *
 * @ingroup ext_redirect
 *
 * @ContentEntityType(
 *   id = "redirect_rule",
 *   label = @Translation("Redirect Rule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ext_redirect\RedirectRuleListBuilder",
 *     "views_data" = "Drupal\ext_redirect\Entity\RedirectRuleViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ext_redirect\Form\RedirectRuleForm",
 *       "add" = "Drupal\ext_redirect\Form\RedirectRuleForm",
 *       "edit" = "Drupal\ext_redirect\Form\RedirectRuleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\ext_redirect\RedirectRuleAccessControlHandler",
 *   },
 *   base_table = "redirect_rule",
 *   translatable = FALSE,
 *   admin_permission = "manage redirect rule entities",
 *   entity_keys = {
 *     "id" = "rid",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/redirect_rule/{redirect_rule}",
 *     "add-form" = "/admin/config/search/redirect_rule/add",
 *     "edit-form" = "/admin/config/search/redirect_rule/{redirect_rule}/edit",
 *     "delete-form" = "/admin/config/search/redirect_rule/{redirect_rule}/delete",
 *     "collection" = "/admin/config/search/redirect_rule",
 *   },
 *   field_ui_base_route = "redirect_rule.settings"
 *
 * )
 */
class RedirectRule extends ContentEntityBase implements RedirectRuleInterface {

  use EntityChangedTrait;

  public static function createFromArray(array $values) {
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'status' => 1,
    ];
    return \Drupal::entityTypeManager()
      ->getStorage('redirect_rule')
      ->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $this->setDefaultLangcode();
    if ($this->isNew()) {
      $this->set('uuid', $this->uuidGenerator()->generate());
      $this->setCreatedTime(REQUEST_TIME);
    }
    else {
      $this->setChangedTime(REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $paths = $this->getSourcePath();
    $paths = explode("\n", $paths);
    $paths = implode(',', $paths);
    $paths = '[' . $paths . ']';
    return $this->getSourceSite() . ' ' . $paths . ' -> ' . $this->getDestinationUrl()
        ->toString();
  }

  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceSite($site) {
    $this->set('source_site', $site);
    return $this;
  }

  /**
   * Get source host name.
   *
   * @return string
   *    Source site host name.
   */
  public function getSourceSite() {
    return $this->get('source_site')->value;
  }

  /**
   * Set rule source paths. May receive string or array of source paths.
   *
   * @param string|array $path
   *
   * @return $this
   */
  public function setSourcePath($path) {
    if (is_array($path)) {
      foreach ($path as $idx => $item) {
        $item = trim($item);
        $item = rtrim($item, '/');
        $path[$idx] = $item;
      }
      $path = implode("\n", $path);
    }
    else {
      $path = trim($path);
      $path = rtrim($path, '/');
    }
    $this->set('source_path', $path);
    return $this;
  }

  /**
   * Get rule source paths.
   *
   * @return string
   *    Rule source paths.
   */
  public function getSourcePath() {
    $field = $this->get('source_path');
    $value = $field->getValue();
    return !empty($value) ? reset($value) : '';
  }

  /**
   * Set rule destination path.
   *
   * @param string $uri
   *    Rule destination. External url or internal path.
   *
   * @return $this
   */
  public function setDestination($uri) {
    // Try to change the internal: scheme to entity: scheme if possible.
    // That way, we don't break a redirect if the alias of a node changes and
    // we store redirect rules the same way for imported rules, as it would be
    // stored if created via the backend form.
    // @TODO What about <front> as destination?
    if (strpos($uri, 'http') === FALSE) {
      $uri = 'internal:/' . ltrim($uri, '/');
      $url = Url::fromUri($uri);
      // Check if this Url is routed.
      $route_name = $url->isRouted() ? $url->getRouteName() : FALSE;
      if ($route_name && in_array($route_name, ['entity.node.canonical'])) {
        $uri = 'entity:node/' . $url->getRouteParameters()['node'];
      }
    }

    $this->set('destination_uri', ['uri' => $uri]);
    return $this;
  }

  /**
   * Get rule destination URI.
   *
   * @return string
   *   Rule destination URI.
   */
  public function getDestination() {
    return $this->get('destination_uri')->get(0)->getValue()['uri'];
  }

  /**
   * Gets the redirect URL.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  public function getDestinationUrl() {
    return $this->get('destination_uri')->get(0)->getUrl();
  }

  public function getDestinationUrlOptions() {
    return $this->get('destination_uri')->get(0)->getValue()['options'];
  }

  /**
   * Set status code for HTTP redirect. 301 in most cases.
   *
   * @param int $code
   *    Rule status code, like 301.
   *
   * @return $this
   */
  public function setStatusCode($code) {
    $this->set('status_code', $code);
    return $this;
  }

  /**
   * Get rule HTTP status code.
   *
   * @return int
   *    HTTP status code value
   */
  public function getStatusCode() {
    return $this->get('status_code')->value;
  }

  /**
   * Turn of current rule.
   *
   * @return $this
   */
  public function setEnabled() {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * Pass TRUE if you want to enable current rule.
   *
   * @param bool $status
   *    Rule status flag.
   *
   * @return $this
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Get rule status.
   *
   * @return bool
   *   TRUE if rule is enabled.
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Get rule weight.
   *
   * @return int
   *    Rule weight
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * Set rule weight value.
   *
   * @param int $weight
   *    Weight value.
   *
   * @return $this
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Redirect Rule ID'))
      ->setDescription(t('The redirect rule ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Redirect Rule entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source_site'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source Site'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDescription(t('The Redirect Rule source site name'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
        'allowed_values_function' => [
          'Drupal\ext_redirect\RedirectRuleHelper',
          'extRedirectSourceSitesAllowedValues',
        ],
      ])
      ->setDefaultValue('any')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source_path'] = BaseFieldDefinition::create('source_path')
      ->setLabel(t('Source Path'))
      ->setRevisionable(TRUE)
      ->setDescription(t('Source path. May store multi paths, separated by newline.'))
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['destination_uri'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Destination'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);
    /**
     * @var RedirectRuleHelper $extRedirectHelper
     */
    $extRedirectHelper = \Drupal::service('ext_redirect.helper');

    $fields['status_code'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Status code'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDescription(t('The redirect status code.'))
      ->setSetting('allowed_values', $extRedirectHelper->extRedirectStatusCodes())
      ->setDefaultValue(301)
      ->setDisplayOptions('form', [
        'type' => 'options_list',
        'weight' => 4,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the Redirect Rule is active'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The redirect rule weight value'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'weight' => 4,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  public function delete() {
    parent::delete();

    // Invalidate all cache entries which are tagged with "ext_redirect".
    Cache::invalidateTags(['ext_redirect']);
  }
}
