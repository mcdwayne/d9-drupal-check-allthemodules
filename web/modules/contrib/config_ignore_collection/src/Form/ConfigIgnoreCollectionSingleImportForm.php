<?php

namespace Drupal\config_ignore_collection\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\config_ignore_collection\StorageComparer;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\config\Form\ConfigSingleImportForm;

/**
 * Provides a form for importing a single configuration file.
 *
 * @internal
 */
class ConfigIgnoreCollectionSingleImportForm extends ConfigSingleImportForm {

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The confirmation step needs no additional validation.
    if ($this->data) {
      return;
    }

    try {
      // Decode the submitted import.
      $data = Yaml::decode($form_state->getValue('import'));
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('import', $this->t('The import failed with the following message: %message', ['%message' => $e->getMessage()]));
    }

    // Validate for config entities.
    if ($form_state->getValue('config_type') !== 'system.simple') {
      $definition = $this->entityManager->getDefinition($form_state->getValue('config_type'));
      $id_key = $definition->getKey('id');

      // If a custom entity ID is specified, override the value in the
      // configuration data being imported.
      if (!$form_state->isValueEmpty('custom_entity_id')) {
        $data[$id_key] = $form_state->getValue('custom_entity_id');
      }

      $entity_storage = $this->entityManager->getStorage($form_state->getValue('config_type'));
      // If an entity ID was not specified, set an error.
      if (!isset($data[$id_key])) {
        $form_state->setErrorByName('import', $this->t('Missing ID key "@id_key" for this @entity_type import.', ['@id_key' => $id_key, '@entity_type' => $definition->getLabel()]));
        return;
      }

      $config_name = $definition->getConfigPrefix() . '.' . $data[$id_key];
      // If there is an existing entity, ensure matching ID and UUID.
      if ($entity = $entity_storage->load($data[$id_key])) {
        $this->configExists = $entity;
        if (!isset($data['uuid'])) {
          $form_state->setErrorByName('import', $this->t('An entity with this machine name already exists but the import did not specify a UUID.'));
          return;
        }
        if ($data['uuid'] !== $entity->uuid()) {
          $form_state->setErrorByName('import', $this->t('An entity with this machine name already exists but the UUID does not match.'));
          return;
        }
      }
      // If there is no entity with a matching ID, check for a UUID match.
      elseif (isset($data['uuid']) && $entity_storage->loadByProperties(['uuid' => $data['uuid']])) {
        $form_state->setErrorByName('import', $this->t('An entity with this UUID already exists but the machine name does not match.'));
      }
    }
    else {
      $config_name = $form_state->getValue('config_name');
      $config = $this->config($config_name);
      $this->configExists = !$config->isNew() ? $config : FALSE;
    }

    // Use ConfigImporter validation.
    if (!$form_state->getErrors()) {
      $source_storage = new StorageReplaceDataWrapper($this->configStorage);
      $source_storage->replaceData($config_name, $data);
      $storage_comparer = new StorageComparer(
        $source_storage,
        $this->configStorage,
        $this->configManager
      );

      if (!$storage_comparer->createChangelist()->hasChanges()) {
        $form_state->setErrorByName('import', $this->t('There are no changes to import.'));
      }
      else {
        $config_importer = new ConfigImporter(
          $storage_comparer,
          $this->eventDispatcher,
          $this->configManager,
          $this->lock,
          $this->typedConfigManager,
          $this->moduleHandler,
          $this->moduleInstaller,
          $this->themeHandler,
          $this->getStringTranslation()
        );

        try {
          $config_importer->validate();
          $form_state->set('config_importer', $config_importer);
        }
        catch (ConfigImporterException $e) {
          // There are validation errors.
          $item_list = [
            '#theme' => 'item_list',
            '#items' => $config_importer->getErrors(),
            '#title' => $this->t('The configuration cannot be imported because it failed validation for the following reasons:'),
          ];
          $form_state->setErrorByName('import', $this->renderer->render($item_list));
        }
      }
    }

    // Store the decoded version of the submitted import.
    $form_state->setValueForElement($form['import'], $data);
  }

}
