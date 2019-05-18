<?php

namespace Drupal\config_src\Form;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Url;
use Drupal\config\Form\ConfigSync;
use Drupal\Core\Config\FileStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Construct the storage changes in a configuration synchronization form.
 */
class ConfigSrc extends ConfigSync {
  /**
   * Configuration directory.
   */
  private $config_source;

  /**
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The source storage.
   */
  private function setSyncStorage(StorageInterface $sync_storage) {
    $this->syncStorage = $sync_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_src_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->config_source = 'sync';

    if (isset($_GET['config_source'])) {
      $this->config_source = $_GET['config_source'];
    }

    if (!isset($GLOBALS['config_directories'][$this->config_source])) {
      if ('sync' != $this->config_source) {
        return new RedirectResponse(Url::fromRoute('config.sync')->toString());
      }
      else {
        reset($GLOBALS['config_directories']);
        $first_key = key($GLOBALS['config_directories']);

        $options = array(
          'query' => array(
            'config_source' => $first_key,
          ),
        );

        return new RedirectResponse(Url::fromRoute('config.sync', array(), $options)->toString());
      }
    }

    $config_storage = new FileStorage($GLOBALS['config_directories'][$this->config_source]);
    $this->setSyncStorage($config_storage);

    $form['config_directory'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Config source'),
      '#description' => t('Select config source directory'),
      '#default_value' => $GLOBALS['config_directories'][$this->config_source],
      '#options' => array_flip($GLOBALS['config_directories']),
    );

    $form['config_directory_switch'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
      '#submit' => array('::submitFormConfigSwitch'),
    );

    $form['actions'] = array('#type' => 'actions');

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import all'),
    );

    $source_list = $this->syncStorage->listAll();
    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()->hasChanges()) {
      $form['no_changes'] = array(
        '#type' => 'table',
        '#header' => array('Name', 'Operations'),
        '#rows' => array(),
        '#empty' => $this->t('There are no configuration changes to import.'),
      );
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    elseif (!$storage_comparer->validateSiteUuid()) {
      drupal_set_message($this->t('The staged configuration cannot be imported, because it originates from a different site than this site. You can only synchronize configuration between cloned instances of this site.'), 'error');
      $form['actions']['#access'] = FALSE;
      return $form;
    }
    // A list of changes will be displayed, so check if the user should be
    // warned of potential losses to configuration.
    if ($this->snapshotStorage->exists('core.extension')) {
      $snapshot_comparer = new StorageComparer($this->activeStorage, $this->snapshotStorage, $this->configManager);
      if (!$form_state->getUserInput() && $snapshot_comparer->createChangelist()->hasChanges()) {
        $change_list = array();
        foreach ($snapshot_comparer->getAllCollectionNames() as $collection) {
          foreach ($snapshot_comparer->getChangelist(NULL, $collection) as $config_names) {
            if (empty($config_names)) {
              continue;
            }
            foreach ($config_names as $config_name) {
              $change_list[] = $config_name;
            }
          }
        }
        sort($change_list);
        $message = [
          [
            '#markup' => $this->t('The following items in your active configuration have changes since the last import that may be lost on the next import.')
          ],
          [
            '#theme' => 'item_list',
            '#items' => $change_list,
          ]
        ];
        drupal_set_message($this->renderer->renderPlain($message), 'warning');
      }
    }

    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);

    // Add the AJAX library to the form for dialog support.
    $form['#attached']['library'][] = 'core/drupal.ajax';

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      if ($collection != StorageInterface::DEFAULT_COLLECTION) {
        $form[$collection]['collection_heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@collection configuration collection', array('@collection' => $collection)),
        );
      }
      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {
        if (empty($config_names)) {
          continue;
        }

        // @todo A table caption would be more appropriate, but does not have the
        //   visual importance of a heading.
        $form[$collection][$config_change_type]['heading'] = array(
          '#type' => 'html_tag',
          '#tag' => 'h3',
        );
        switch ($config_change_type) {
          case 'create':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count new', '@count new');
            break;

          case 'update':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count changed', '@count changed');
            break;

          case 'delete':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count removed', '@count removed');
            break;

          case 'rename':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count renamed', '@count renamed');
            break;
        }
        $form[$collection][$config_change_type]['list'] = array(
          '#type' => 'table',
          '#header' => array('Name', 'Operations'),
        );

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $route_options = array('source_name' => $names['old_name'], 'target_name' => $names['new_name']);
            $config_name = $this->t('@source_name to @target_name', array('@source_name' => $names['old_name'], '@target_name' => $names['new_name']));
          }
          else {
            $route_options = array('source_name' => $config_name);
            $route_options_import = array('config_name' => $config_name);
          }

          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            $route_name = 'config_src.diff_collection';
            $route_options['collection'] = $collection;
            $route_name_import = 'config_src.single_import_collection';
            $route_options_import['collection'] = $collection;
          }
          else {
            $route_name = 'config_src.diff';
            $route_name_import = 'config_src.single_import';
          }

          $route_options['config_source'] = $this->config_source;
          $links['view_diff'] = array(
            'title' => $this->t('View differences'),
            'url' => Url::fromRoute($route_name, $route_options),
            'attributes' => array(
              'class' => array('use-ajax'),
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode(array(
                'width' => 700,
              )),
            ),
          );

          if (isset($route_options_import['config_name'])) {
            $route_options_import['config_source'] = $this->config_source;
            $links['import'] = array(
              'title' => $this->t('Import config'),
              'url' => Url::fromRoute($route_name_import, $route_options_import),
            );
          }

          $form[$collection][$config_change_type]['list']['#rows'][] = array(
            'name' => $config_name,
            'operations' => array(
              'data' => array(
                '#type' => 'operations',
                '#links' => $links,
              ),
            ),
          );
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config_dirs = array_flip($GLOBALS['config_directories']);
    $config_directory = $form_state->getValue('config_directory');
    $options = array(
      'config_source' => $config_dirs[$config_directory],
    );

    $form_state->setRedirect('config.sync', $options);
  }

  /**
   * Form config source switch submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitFormConfigSwitch(array &$form, FormStateInterface $form_state) {
    // Switch config directory.
    $config_dirs = array_flip($GLOBALS['config_directories']);
    $config_directory = $form_state->getValue('config_directory');
    $options = array(
      'config_source' => $config_dirs[$config_directory],
    );

    $form_state->setRedirect('config.sync', $options);
  }

}
