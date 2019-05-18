<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Entity\Link.
 */

namespace Drupal\colossal_menu\Entity;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\colossal_menu\LinkInterface;
use Drupal\link\LinkItemInterface;

/**
 * Defines the Link entity.
 *
 * @ingroup colossal_menu
 *
 * @ContentEntityType(
 *   id = "colossal_menu_link",
 *   label = @Translation("Link"),
 *   bundle_label = @Translation("Link type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\colossal_menu\LinkListBuilder",
 *     "views_data" = "Drupal\colossal_menu\Entity\LinkViewsData",
 *     "form" = {
 *       "default" = "Drupal\colossal_menu\Form\LinkForm",
 *       "add" = "Drupal\colossal_menu\Form\LinkForm",
 *       "edit" = "Drupal\colossal_menu\Form\LinkForm",
 *       "delete" = "Drupal\colossal_menu\Form\LinkDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\colossal_menu\LinkAccessControlHandler",
 *   },
 *   base_table = "colossal_menu_link",
 *   admin_permission = "administer link entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/colossal_menu/{colossal_menu}/link/{colossal_menu_link}",
 *     "add-form" = "/admin/structure/colossal_menu/{colossal_menu}/link/add",
 *     "edit-form" = "/admin/structure/colossal_menu/{colossal_menu}/link/{colossal_menu_link}",
 *     "delete-form" = "/admin/structure/colossal_menu/{colossal_menu}/link/{colossal_menu_link}/delete",
 *   },
 *   bundle_entity_type = "colossal_menu_link_type",
 *   field_ui_base_route = "entity.colossal_menu_link_type.edit_form"
 * )
 */
class Link extends ContentEntityBase implements LinkInterface {
  use EntityChangedTrait;

  /**
   * Database Connection.
   *
   * @var \DatabaseConnection
   */
  protected $connection;

  /**
   * Url Object.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * {@inheritdoc}
   *
   * Update the link tree.
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $connection = $this->getConnection();

    if (!$update) {
      // Add the Link to itself.
      $connection->insert('colossal_menu_link_tree')
        ->fields([
          'ancestor' => $this->id(),
          'descendant' => $this->id(),
          'depth' => 0,
        ])
        ->execute();

      if ($this->getParent()) {
        // Get the tree of the link's parent.
        $result = $connection->select('colossal_menu_link_tree', 't')
          ->fields('t', ['ancestor', 'depth'])
          ->condition('t.descendant', $this->getParent()->id())
          ->execute();

        while ($row = $result->fetchObject()) {
          $connection->insert('colossal_menu_link_tree')
            ->fields([
              'ancestor' => $row->ancestor,
              'descendant' => $this->id(),
              'depth' => $row->depth + 1,
            ])
            ->execute();
        }
      }
    }
    else {

      // First get the link's tree below itself.
      $query = $connection->select('colossal_menu_link_tree', 't')
        ->fields('t', ['descendant', 'depth'])
        ->condition('t.ancestor', $this->id());
      $result = $query->execute();

      $descendants = [];
      $ids = [];
      while ($row = $result->fetchObject()) {
        $descendants[] = [
          'descendant' => $row->descendant,
          'depth' => $row->depth,
        ];
        $ids[] = $row->descendant;
      }

      // Then delete the link tree above the current link.
      if (!empty($ids)) {
        $connection->delete('colossal_menu_link_tree')
          ->condition('descendant', $ids, 'IN')
          ->condition('ancestor', $ids, 'NOT IN')
          ->execute();
      }

      if ($this->getParent()) {
        // Finally, copy the tree from the new parent.
        $result = $connection->select('colossal_menu_link_tree', 't')
          ->fields('t', ['ancestor', 'depth'])
          ->condition('t.descendant', $this->getParent()->id())
          ->execute();

        while ($row = $result->fetchObject()) {
          foreach ($descendants as $descendant) {
            $connection->insert('colossal_menu_link_tree')
              ->fields([
                'ancestor' => $row->ancestor,
                'descendant' => $descendant['descendant'],
                'depth' => $row->depth + $descendant['depth'] + 1,
              ])
              ->execute();
          }
        }

      }
    }

    return parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   *
   * Update the link tree.
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    $connection = \Drupal::service('database');

    foreach ($entities as $entity) {
      $query = $connection->delete('colossal_menu_link_tree');
      $or = new Condition('OR');
      $or->condition('ancestor', $entity->id());
      $or->condition('descendant', $entity->id());
      $query->condition($or);
      $query->execute();
    }

    return parent::postDelete($storage, $entities);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Link entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Link type/bundle.'))
      ->setSetting('target_type', 'colossal_menu_link_type')
      ->setRequired(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Link entity.'))
      ->setReadOnly(TRUE);

    $fields['menu'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Menu'))
      ->setDescription(t('The menu of the Link entity.'))
      ->setSetting('target_type', 'colossal_menu')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('The parent item'))
      ->setSetting('target_type', 'colossal_menu_link')
      ->setSetting('handler', 'default');

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The text to be used for this link in the menu.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ]);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('Machine name of the menu link'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->addConstraint('UniqueField', [])
      ->setDisplayOptions('form', [
        'type' => 'machine_name',
        'weight' => -4,
        'settings' => [
          'source' => [
            'title',
            'widget',
            0,
            'value',
          ],
          'exists' => '\Drupal\colossal_menu\Entity\Link::loadByMachineName',
        ],
      ]);

    $fields['show_title'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show Title'))
      ->setDescription(t('A flag for whether the title should be shown in menus or hidden.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -4,
      ]);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('A flag for whether the link should be enabled in menus or hidden.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => -1,
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
      ->setDefaultValue(0);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Link entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->get('menu')->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    return $this->set('weight', $weight);
  }

  /**
   * {@inheritdoc}
   */
  public function setEnabled($enabled) {
    return $this->set('enabled', $enabled);
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->get('parent')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent) {
    return $this->set('parent', $parent);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    return $this->getUrlObject()->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public function showTitle() {
    return (bool) $this->get('show_title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    if ($this->getUrlObject()->isRouted()) {
      return $this->getUrlObject()->getRouteName();
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    if ($this->getUrlObject()->isRouted()) {
      return $this->getUrlObject()->getRouteParameters();
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    if (!$this->url) {
      if ($this->get('link')->isEmpty()) {
        $this->url = Url::fromUri('internal:');
      }
      else {
        $this->url = $this->get('link')->first()->getUrl();
      }
    }

    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->get('link')->first()->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaData() {
    return $this->toArray();
  }

  /**
   * {@inheritdoc}
   *
   * Not sure what this would do in this context.
   */
  public function updateLink(array $new_definition_values, $persist) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    return $this->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass() {
    return $this->getEntityKey('handlers')['form']['default'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return Url::fromRoute('entity.colossal_menu_link.delete_form', [
      'colossal_menu' => $this->getMenuName(),
      'colossal_menu_link' => $this->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return Url::fromRoute('entity.colossal_menu_link.edit_form', [
      'colossal_menu' => $this->getMenuName(),
      'colossal_menu_link' => $this->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    return $this->getEditRoute();
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);

    if (in_array($rel, ['canonical', 'edit-form', 'delete-form'])) {
      $params['colossal_menu'] = $this->getMenuName();
    }

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return 'colossal_menu';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'colossal_menu_link:' . $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return [
      'enabled' => $this->isEnabled(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseId() {
    return 'colossal_menu_link';
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeId() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->get('machine_name')->value;
  }

  /**
   * Checks that an existing machine name does not already exist.
   *
   * This is a static mehod so it can be used by a machine name field.
   *
   * @param string $machine_name
   *   The machine name to load the entity by.
   *
   * @return \Drupal\colossal_menu\Entity\Link|NULL
   *   Loaded Link entity or NULL if not found.
   */
  public static function loadByMachineName($machine_name) {
    $storage = \Drupal::service('entity.manager')->getStorage('colossal_menu_link');
    $result = $storage->getQuery()
      ->condition('machine_name', $machine_name)
      ->execute();
    return $result ? $storage->loadMultiple($result) : [];
  }

  /**
   * Get the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  protected function getConnection() {
    if (!$this->connection) {
      $this->connection = $this->container()->get('database');
    }
    return $this->connection;
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

}
