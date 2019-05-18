<?php

/**
 * @file
 * Contains \Drupal\demo_content\Form\DemoContentImportForm.
 */

namespace Drupal\demo_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DemoContentImportForm
 *
 * @package Drupal\demo_content\Form
 */
class DemoContentImportForm extends FormBase {

  /**
   * An array of Extensions.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $extensions;

  /**
   * DemoContentImportForm constructor.
   */
  public function __construct() {
    $this->extensions = \Drupal::service('demo_content.extension_manager')
      ->getExtensions();

//    $definitions = \Drupal::service('entity_type.manager')->getDefinitions();
//    $bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_content_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If no demo extensions found, return empty form.
    if (!count($this->extensions)) {
      return [
        'message' => [
          '#markup' => t('No demo extensions found.'),
        ]
      ];
    }

    // Build form.
    $form = [];
    $form['#attached']['library'][] = 'demo_content/admin';
    $form['demo_content']['#tree'] = TRUE;
    $form['#attributes']['class'][] = 'demo-content-import-form';

    foreach ($this->extensions as $extension) {
      $header = array(
        'entity_type' => t('Entity type'),
        'bundle' => t('Bundle'),
        'entities' => t('Entities'),
        'path' => t('Path'),
      );

      // Build options array.
      $options = [];
      foreach ($extension->info['demo_content'] as $path => $demo_content) {
        $entity_type_id = $demo_content['entity_type'];
        $entity_type = \Drupal::service('entity_type.manager')->getDefinition($entity_type_id);
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);
        $options[$path] = [
          'entity_type' => $entity_type->getLabel(),
          'bundle' => $bundles[$demo_content['bundle']]['label'],
          'entities' => count($demo_content['content']),
          'path' => $extension->getPath() . '/' . $path,
        ];
      }

      $form['demo_content'][$extension->getName()] = [
        '#prefix' => '<h3>' . $extension->info['name'] . '</h3>',
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $options,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions'
    ];

    $form['actions']['op'] = [
      '#type' => 'submit',
      '#value' => t('Import'),
      '#attributes' => ['class' => ['button--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $demo_content = $form_state->getValue('demo_content');

    foreach ($demo_content as $extension_name => $files) {
      if (isset($this->extensions[$extension_name])) {
        // Filter out empty files.
        $files = array_filter($files);

        foreach ($files as $file) {
          // Get the content values for the demo_content file.
          $content = $this->extensions[$extension_name]->info['demo_content'][$file];

          // Import entities for each file.
          $entities = \Drupal::service('demo_content.manager')
            ->import($content);

          // Show a success message.
          drupal_set_message(t('@count entities created', [
            '@count' => count($entities),
          ]));
        }
      }
    }
  }
}
