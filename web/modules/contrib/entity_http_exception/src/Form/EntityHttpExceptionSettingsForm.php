<?php

namespace Drupal\entity_http_exception\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_http_exception\Utils\EntityHttpExceptionUtils as Utils;

/**
 * Configure example settings for this site.
 */
class EntityHttpExceptionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_http_exception_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_http_exception.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_http_exception.settings');

    $entity_types = Utils::getEntityTypes();

    foreach ($entity_types as $entity_type => $entity) {
      $bundles = Utils::getEntityBundles($entity_type);
      $form[$entity_type] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@entity_label', ['@entity_label' => $entity['title']]),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      foreach ($bundles as $bundle_name => $bundle_label) {

        $code_key_name = Utils::getHttpExceptionCodeKey($entity['key'], $bundle_name);
        $form[$entity_type][$bundle_name] = [
          '#type' => 'details',
          '#title' => $bundle_label,
          '#open' => !empty($config->get($code_key_name) && $config->get($code_key_name) != 0) ? TRUE : FALSE,
        ];

        if ($entity_type == 'node_type') {
          $key_name = Utils::getUnpublishedNodesKey($bundle_name);
          $form[$entity_type][$bundle_name][$key_name] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Http exception react on unpublish nodes'),
            '#default_value' => !empty($config->get($key_name)) ? $config->get($key_name) : '',
          ];

          $key_name = Utils::getPublishedNodesKey($bundle_name);
          $form[$entity_type][$bundle_name][$key_name] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Http exception react on publish nodes'),
            '#default_value' => !empty($config->get($key_name)) ? $config->get($key_name) : '',
          ];
        }

        $key_name = Utils::getHttpExceptionCodeKey($entity['key'], $bundle_name);
        $form[$entity_type][$bundle_name][$key_name] = [
          '#type' => 'select',
          '#title' => $this->t('Http Exception'),
          '#description' => $this->t('Select a HTTP exception for specific entity.'),
          '#options' => [
            0 => $this->t('- Please select a http exception -'),
            404 => $this->t('404 - Page not found'),
            403 => $this->t('403 - Access denied'),
          ],
          '#default_value' => !empty($config->get($key_name)) ? $config->get($key_name) : '',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('entity_http_exception.settings');
    $entity_types = Utils::getEntityTypes();
    foreach ($entity_types as $entity_type => $entity) {
      $bundles = Utils::getEntityBundles($entity_type);
      foreach ($bundles as $bundle_name => $bundle_label) {

        if ($entity_type == 'node_type') {
          $element_name = Utils::getUnpublishedNodesKey($bundle_name);
          $unpublished_node = $form_state->getValue($element_name);
          $config->set($element_name, $unpublished_node);

          $element_name = Utils::getPublishedNodesKey($bundle_name);
          $unpublished_node = $form_state->getValue($element_name);
          $config->set($element_name, $unpublished_node);
        }

        $element_name = Utils::getHttpExceptionCodeKey($entity['key'], $bundle_name);
        $exception_code = $form_state->getValue($element_name);
        $config->set($element_name, $exception_code);
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
