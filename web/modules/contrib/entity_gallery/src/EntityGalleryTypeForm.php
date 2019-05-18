<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for entity gallery type forms.
 */
class EntityGalleryTypeForm extends BundleEntityFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs the EntityGalleryTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_gallery\EntityGalleryTypeInterface $type */
    $type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add gallery type');
      $fields = $this->entityManager->getBaseFieldDefinitions('entity_gallery');
      // Create an entity gallery with a fake bundle using the type's UUID so
      // that we can get the default values for workflow settings.
      // @todo Make it possible to get default values without an entity.
      //   https://www.drupal.org/node/2318187
      $entity_gallery = $this->entityManager->getStorage('entity_gallery')->create(array('type' => $type->uuid()));
    }
    else {
      $form['#title'] = $this->t('Edit %label gallery type', array('%label' => $type->label()));
      $fields = $this->entityManager->getFieldDefinitions('entity_gallery', $type->id());
      // Create an entity gallery to get the current values for workflow
      // settings fields.
      $entity_gallery = $this->entityManager->getStorage('entity_gallery')->create(array('type' => $type->id()));
    }

    $form['name'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this gallery type. This text will be displayed as part of the list on the <em>Add gallery</em> page. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['type'] = array(
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $type->isLocked(),
      '#machine_name' => array(
        'exists' => ['Drupal\entity_gallery\Entity\EntityGalleryType', 'load'],
        'source' => array('name'),
      ),
      '#description' => t('A unique machine-readable name for this gallery type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %entity-gallery-add page, in which underscores will be converted into hyphens.', array(
        '%entity-gallery-add' => t('Add gallery'),
      )),
    );














    // Determine the currently selected entity type.
    $selected = !empty($form_state->getValue('gallery_type')) ? $form_state->getValue('gallery_type') : $type->getGalleryType();

    $options = array();
    $entity_types = $this->entityManager->getDefinitions();

    foreach ($entity_types as $entity_type) {
      $options[$entity_type->id()] = $entity_type->getLabel();
    }

    $has_data = FALSE;

    // Disable entity type selection if the entity reference field already
    // contains data.
    $field_storage = FieldStorageConfig::loadByName('entity_gallery', 'gallery_' . $selected);
    if ($field_storage) {
      $has_data = $field_storage->hasData();
    }

    $form['gallery_type'] = array(
      '#type' => 'select',
      '#title' => t('Entity type'),
      '#default_value' => $type->getGalleryType(),
      '#options' => $this->getEntityTypeOptions(),
      '#empty_option' => t('- Select an entity type -'),
      '#description' => t('The type of entity allowed to be added to the gallery.'),
      '#disabled' => $has_data,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::dependent_dropdown_degrades_first_callback',
        'wrapper' => 'dropdown-second-replace',
      ),
    );

    // The user must select an entity type before the entity type bundles can be
    // determined.
    $form['select_gallery_type'] = array(
      '#type' => 'submit',
      '#value' => t('Choose'),
      '#attributes' => array(
        'class' => array('next-button'),
      ),
    );

    $bundle_options = array();

    if ($selected) {
      $gallery_type = $this->entityTypeManager->getDefinition($selected);
      $bundle_options = $this->getEntityBundleOptions($gallery_type);
    }

    if (isset($options[$selected])) {
      $title = t('@gallery_type bundles', array('@gallery_type' => $options[$selected]));
    }
    else {
      $title = t('Bundles');
    }

    $form['gallery_type_bundles'] = array(
      '#type' => 'checkboxes',
      '#title' => $title,
      '#default_value' => !empty($type->getGalleryTypeBundles()) ? $type->getGalleryTypeBundles() : array(),
      '#options' => array(),
      '#description' => t('The selected entity type only contains one bundle which is always allowed.'),
      '#prefix' => '<div id="dropdown-second-replace">',
      '#suffix' => '</div>',
    );

    // Alert the user that they must choose an gallery_type before selecting
    // gallery_type_bundles.
    if (empty($form_state->getValue('gallery_type'))) {
      $form['gallery_type_bundles']['#description'] = t('You must choose an entity type before selecting its bundles.');
    }

    // The selection is hidden if there's just one option, since that's always
    // going to be allowed.
    if (count($bundle_options) > 1) {
      $form['gallery_type_bundles']['#required'] = TRUE;
      $form['gallery_type_bundles']['#options'] = $bundle_options;
      $form['gallery_type_bundles']['#description'] = t('Select one or more bundles to restrict adding to. If none are selected, all are allowed.');
    }


    $form['old_gallery_type'] = array(
      '#type' => 'value',
      '#value' => $type->getGalleryType(),
    );
















    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add new gallery</em> page.'),
    );

    $form['additional_settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array('entity_gallery/drupal.entity_gallery_types'),
      ),
    );

    $form['submission'] = array(
      '#type' => 'details',
      '#title' => t('Submission form settings'),
      '#group' => 'additional_settings',
      '#open' => TRUE,
    );
    $form['submission']['title_label'] = array(
      '#title' => t('Title field label'),
      '#type' => 'textfield',
      '#default_value' => $fields['title']->getLabel(),
      '#required' => TRUE,
    );
    $form['submission']['preview_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Preview before submitting'),
      '#default_value' => $type->getPreviewMode(),
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );
    $form['submission']['help']  = array(
      '#type' => 'textarea',
      '#title' => t('Explanation or submission guidelines'),
      '#default_value' => $type->getHelp(),
      '#description' => t('This text will be displayed at the top of the page when creating or editing galleries of this type.'),
    );
    $form['workflow'] = array(
      '#type' => 'details',
      '#title' => t('Publishing options'),
      '#group' => 'additional_settings',
    );
    $workflow_options = array(
      'status' => $entity_gallery->status->value,
      'revision' => $type->isNewRevision(),
    );
    // Prepare workflow options to be used for 'checkboxes' form element.
    $keys = array_keys(array_filter($workflow_options));
    $workflow_options = array_combine($keys, $keys);
    $form['workflow']['options'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Default options'),
      '#default_value' => $workflow_options,
      '#options' => array(
        'status' => t('Published'),
        'revision' => t('Create new revision'),
      ),
      '#description' => t('Users with the <em>Administer entity galleries</em> permission will be able to override these options.'),
    );
    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = array(
        '#type' => 'details',
        '#title' => t('Language settings'),
        '#group' => 'additional_settings',
      );

      $language_configuration = ContentLanguageSettings::loadByEntityTypeBundle('entity_gallery', $type->id());
      $form['language']['language_configuration'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'entity_gallery',
          'bundle' => $type->id(),
        ),
        '#default_value' => $language_configuration,
      );
    }
    $form['display'] = array(
      '#type' => 'details',
      '#title' => t('Display settings'),
      '#group' => 'additional_settings',
    );
    $form['display']['display_submitted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display author and date information'),
      '#default_value' => $type->displaySubmitted(),
      '#description' => t('Author username and publish date will be displayed.'),
    );

    return $this->protectBundleIdElement($form);
  }

  /**
   * Selects just the second dropdown to be returned for re-rendering.
   */
  public function dependent_dropdown_degrades_first_callback($form, FormStateInterface $form_state) {
    return $form['gallery_type_bundles'];
  }

  /**
   * Builds a list of entity type options.
   *
   * Configuration entity types without a view builder are filtered out while
   * all other entity types are kept.
   *
   * @return array
   *   An array of entity type labels, keyed by entity type name.
   */
  protected function getEntityTypeOptions() {
    $options = $this->entityManager->getEntityTypeLabels(TRUE);

    foreach ($options as $group => $group_types) {
      foreach (array_keys($group_types) as $entity_type_id) {
        // Filter out entity types that do not have a view builder class.
        if (!$this->entityTypeManager->getDefinition($entity_type_id)->hasViewBuilderClass()) {
          unset($options[$group][$entity_type_id]);
        }
      }
    }

    return $options;
  }

  /**
   * Builds a list of entity type bundle options.
   *
   * Configuration entity types without a view builder are filtered out while
   * all other entity types are kept.
   *
   * @return array
   *   An array of bundle labels, keyed by bundle name.
   */
  protected function getEntityBundleOptions(EntityTypeInterface $entity_type) {
    $bundle_options = array();
    // If the entity has bundles, allow option to restrict to bundle(s).
    if ($entity_type->hasKey('bundle')) {
      foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle_id => $bundle_info) {
        $bundle_options[$bundle_id] = $bundle_info['label'];
      }
      natsort($bundle_options);
    }
    return $bundle_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save gallery type');
    $actions['delete']['#value'] = t('Delete gallery type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('type'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('type', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_gallery\EntityGalleryTypeInterface $type */
    $type = $this->entity;
    $type->setNewRevision($form_state->getValue(array('options', 'revision')));
    $type->set('type', trim($type->id()));
    $type->set('name', trim($type->label()));

    $gallery_type = $form_state->getValue('gallery_type');
    $gallery_type_bundles = $form_state->getValue('gallery_type_bundles');
    $old_gallery_type = $form_state->getValue('old_gallery_type');

    $status = $type->save();

    $t_args = array('%name' => $type->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The gallery type %name has been updated.', $t_args));
      // Accommodate changes to the entity reference entity type.
      if ($old_gallery_type != $gallery_type) {
        entity_gallery_delete_entity_reference_field($type, $old_gallery_type);
        entity_gallery_create_entity_reference_field($type, $gallery_type, $gallery_type_bundles);
      }
      else {
        entity_gallery_update_entity_reference_field($type, $gallery_type, $gallery_type_bundles);
      }
    }
    elseif ($status == SAVED_NEW) {
      entity_gallery_create_entity_reference_field($type, $gallery_type, $gallery_type_bundles);
      drupal_set_message(t('The gallery type %name has been added.', $t_args));
      $context = array_merge($t_args, array('link' => $type->link($this->t('View'), 'collection')));
      $this->logger('entity_gallery')->notice('Added gallery type %name.', $context);
    }

    $fields = $this->entityManager->getFieldDefinitions('entity_gallery', $type->id());
    // Update title field definition.
    $title_field = $fields['title'];
    $title_label = $form_state->getValue('title_label');
    if ($title_field->getLabel() != $title_label) {
      $title_field->getConfig($type->id())->setLabel($title_label)->save();
    }
    // Update workflow options.
    // @todo Make it possible to get default values without an entity.
    //   https://www.drupal.org/node/2318187
    $entity_gallery = $this->entityManager->getStorage('entity_gallery')->create(array('type' => $type->id()));
    foreach (array('status') as $field_name) {
      $value = (bool) $form_state->getValue(['options', $field_name]);
      if ($entity_gallery->$field_name->value != $value) {
        $fields[$field_name]->getConfig($type->id())->setDefaultValue($value)->save();
      }
    }

    $this->entityManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($type->urlInfo('collection'));
  }

}
