<?php

namespace Drupal\crm_core_contact\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crm_core_activity\Entity\Activity;
use Drupal\crm_core_contact\Entity\Contact;
use Drupal\relation\Entity\Relation;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merges 2 or more contacts.
 *
 * @Action(
 *   id = "merge_contacts_action",
 *   label = @Translation("Merge contacts"),
 *   type = "crm_core_contact"
 * )
 */
class MergeContactsAction extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorage
   */
  protected $pathAliasStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $moduleHandler;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a EmailAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasStorage $path_alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasStorage $path_alias_storage, ModuleHandler $module_handler, QueryFactory $entity_query, TranslationManager $translation_manager, EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pathAliasStorage = $path_alias_storage;
    $this->moduleHandler = $module_handler;
    $this->entityQuery = $entity_query;
    $this->translationManager = $translation_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('path.alias_storage'),
      $container->get('module_handler'),
      $container->get('entity.query'),
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : AccessResult::allowed()->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $primary_contact = reset($objects);
    foreach ($objects as $cid => $contact) {
      if ($contact->id() == $this->configuration['data']['contact_id']) {
        $primary_contact = $contact;
        unset($objects[$cid]);
        break;
      }
    }
    unset($this->configuration['data']['contact_id']);
    $wrappers = [];
    foreach ($objects as $contact) {
      $wrappers[$contact->id()] = $contact;
    }
    // Updating contact fields from other selected contacts.
    foreach ($this->configuration['data'] as $field_name => $contact_id) {
      if ($primary_contact->id() != $contact_id) {
        $primary_contact->set($field_name, $wrappers[key($contact_id)]->get($field_name)->getValue());
      }
    }
    $primary_contact->save();
    foreach (array_keys($wrappers) as $contact_id) {
      // Creating path aliases for contacts that will be deleted.
      $this->pathAliasStorage->save('/crm-core/contact/' . $primary_contact->id(), '/crm-core/contact/' . $contact_id);
      if ($this->moduleHandler->moduleExists('crm_core_activity')) {
        // Replacing participant in existing activities.
        $query = $this->entityQuery->get('crm_core_activity');
        $activities = $query->condition('activity_participants.target_id', $contact_id)
          ->condition('activity_participants.target_type', 'crm_core_contact')
          ->execute();
        if (is_array($activities)) {
          foreach (Activity::loadMultiple($activities) as $activity) {
            foreach ($activity->activity_participants as $delta => $participant) {
              if ($participant->target_id == $contact_id) {
                $activity->get('activity_participants')[$delta]->setValue($primary_contact);
              }
            }
            $activity->save();
          }
        }
      }
      if ($this->moduleHandler->moduleExists('relation')) {
        // Replacing existing relations for contacts been deleted with new ones.
        $query = $this->entityQuery->get('relation');
        $relations = $query->condition('endpoints.entity_type', 'crm_core_contact', '=')
          ->condition('endpoints.entity_id', $contact_id, '=')
          ->execute();
        foreach ($relations as $relation_info) {
          $endpoints = [
            ['entity_type' => 'crm_core_contact', 'entity_id' => $primary_contact->id()],
          ];
          $relation = Relation::load($relation_info);
          foreach ($relation->endpoints as $endpoint) {
            if ($endpoint->entity_id != $contact_id) {
              $endpoints[] = [
                'entity_type' => $endpoint->entity_type,
                'entity_id' => $endpoint->entity_id,
              ];
            }
          }
          $new_relation = Relation::create(['relation_type' => $relation->relation_type->target_id, 'endpoints' => $endpoints]);
          $new_relation->save();
        }
      }
    }
    $count = count($wrappers);
    $singular = '%contacts contact merged to %dest.';
    $plural = '%contacts contacts merged to %dest.';
    $contacts_label = array_map(function ($contact) {
      return $contact->label();
    }, $wrappers);
    $message = $this->translationManager->formatPlural($count, $singular, $plural, [
      '%contacts' => implode(', ', $contacts_label),
      '%dest' => $primary_contact->label(),
    ]);
    $this->entityTypeManager->getStorage('crm_core_contact')->delete($wrappers);
    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $primary_contact = array_filter($form_state->getValue('table')['contact_id']);
    if (empty($primary_contact)) {
      $form_state->setError($form['table']['contact_id'], $this->t('You must select primary contact in table header!'));
    }
    if (count($primary_contact) > 1) {
      $form_state->setError($form['table']['contact_id'], $this->t('Supplied more than one primary contact!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $selected_contacts = Contact::loadMultiple($form_state->getValue('selection'));
    $selected_contacts_ids = array_map(function ($contact) {
      return $contact->id();
    }, $selected_contacts);
    // Lets check contacts type, it should be unique.
    $contact_types = array_map(function ($contact) {
      return $contact->type->target_id;
    }, $selected_contacts);
    // All selected contacts have same type.
    if (count(array_unique($contact_types)) != 1) {
      drupal_set_message($this->t('You should select contacts of one type to be able to merge them!'), 'error');
      $form_state->setRedirect('entity.crm_core_contact.collection');
    }
    else {
      $form['table'] = [
        '#type' => 'table',
        '#tree' => TRUE,
        '#selected' => $selected_contacts_ids,
      ];
      // Creating header.
      $header['field_name'] = ['#markup' => $this->t('Field name\\Contact')];
      foreach ($selected_contacts as $contact) {
        $header[$contact->contact_id->value] = [
          '#type' => 'radio',
          '#title' => $contact->label(),
        ];
      }
      $form['table']['contact_id'] = $header;
      $field_instances = $this->entityFieldManager->getFieldDefinitions('crm_core_contact', reset($contact_types));
      unset($field_instances['contact_id']);
      foreach ($field_instances as $field_name => $field_instance) {
        $form['table'][$field_name] = [];
        $form['table'][$field_name]['field_name'] = ['#markup' => $field_instance->getLabel()];
        foreach ($selected_contacts as $contact) {
          $field_value = ['#markup' => ''];
          $contact_field_value = $contact->get($field_name);
          if (isset($contact_field_value)) {
            $field_value_render = $contact_field_value->view('full');
            $field_value_rendered = $this->renderer->render($field_value_render);
            // Some fields can provide empty markup.
            if (!empty($field_value_rendered)) {
              $field_value = [
                '#type' => 'radio',
                '#title' => $field_value_rendered,
              ];
            }
          }
          $form['table'][$field_name][$contact->contact_id->value] = $field_value;
        }
      }
    }

    $form['#attached']['library'][] = 'crm_core_contact/drupal.crm_core_contact.merge-contacts';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $data = ['contact_id' => array_shift(array_keys(array_filter($form_state->getValue('table')['contact_id'])))];
    unset($form_state->getValue('table')['contact_id']);
    foreach ($form_state->getValue('table') as $field_name => $selection) {
      $data[$field_name] = array_shift(array_keys(array_filter($selection)));
    }
    $this->configuration['data'] = array_filter($data);
  }

}
