<?php

namespace Drupal\backstop_generator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class BackstopConfigurationForm.
 */
class BackstopConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'backstop_generator.backstopconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backstop_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('backstop_generator.backstopconfiguration');
    if (empty($form_state->get('pre_defined_pages_table'))) {
      $form_state->set('pre_defined_pages_table', $config->get('pre_defined_pages_table'));
      $entity_count = count($form_state->get('pre_defined_pages_table'));
      if ($entity_count > 0) {
        $form_state->set('num_pages', $entity_count);
        $value = $form_state->get('num_pages');
      }
      else {
        $value = 1;
      }
    }
    $form['viewports'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Viewport(s)'),
      '#description' => $this->t('Select any viewports you&#039;d like to be included for each scenario.'),
      '#options' => [
        'Mobile' => $this->t('Mobile'),
        'Tablet' => $this->t('Tablet'),
        'HD' => $this->t('HD'),
        'HD+' => $this->t('HD+'),
        'UHD' => $this->t('UHD'),
      ],
      '#weight' => '0',
      '#ajax' => [
        'callback' => '::changeViewportsCallback',
        'wrapper' => 'viewports-wrapper',
      ],
      '#default_value' => $config->get('viewports'),
      '#prefix' => '<div id="viewports-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['#tree'] = TRUE;
    $form['pre_defined_pages_table'] = [
      '#type' => 'table',
      '#title' => $this->t('Pre-defined Pages'),
      '#header' => ['Pages', 'Actions'],
      '#prefix' => '<div id="pre-defined-pages-table-wrapper">',
      '#suffix' => '</div>',
    ];
    foreach ($config->get('pre_defined_pages_table') as $key => $value) {
      $configString = 'pre_defined_pages_table.' . $key . '.page';
      if (\Drupal::entityTypeManager()->getStorage('node')->load($config->get($configString))) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($config->get($configString));
      }
      else {
        $node = NULL;
      }
      if ($key != 'actions' || $key != 'add_page') {
        $form['pre_defined_pages_table'][$key]['page'] = [
          '#type' => 'entity_autocomplete',
          '#target_type' => 'node',
          '#weight' => 0,
          '#default_value' => $node,
        ];
        $form['pre_defined_pages_table'][$key]['actions'] = [
          '#type' => 'actions',
        ];
        $form['pre_defined_pages_table'][$key]['actions']['remove_page'] = [
          '#type' => 'submit',
          '#value' => t('Remove one'),
          '#id' => $key,
          '#submit' => ['::removeCallback'],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'pre-defined-pages-table-wrapper',
          ],
        ];
      }
    }
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 2,
    ];
    $form['actions']['add_page'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'pre-defined-pages-table-wrapper',
      ],
      '#weight' => 2,
    ];
    $form['additional_random_pages'] = [
      '#type' => 'number',
      '#title' => $this->t('Additional Random Pages'),
      '#description' => $this->t('Enter a maximum number of randomly-selected pages you&#039;d like to include as testing scenarios.'),
      '#default_value' => $config->get('additional_random_pages'),
      '#weight' => '3',
    ];
    $form_state->setCached(FALSE);
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and View'),
      '#weight' => '4',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function changeViewportsCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('viewports');
    $this->config('backstop_generator.backstopconfiguration')
      ->set('viewports', $values)
      ->save();
    $form_state->setRebuild();
    return $form['viewports'];
  }

  /**
   * {@inheritdoc}
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();
    $newPage = ['page' => NULL];
    $configString = 'pre_defined_pages_table.' . $uuid;
    $this->config('backstop_generator.backstopconfiguration')
      ->set($configString, $newPage)
      ->save();
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['pre_defined_pages_table'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $parentID = $form_state->getTriggeringElement()['#id'];
    $config = \Drupal::service('config.factory')->getEditable('backstop_generator.backstopconfiguration');
    $config->clear('pre_defined_pages_table.' . $parentID)->save();
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function removeCallbackCallback(array &$form, FormStateInterface $form_state) {
    return $form['pre_defined_pages_table'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      $this->config('backstop_generator.backstopconfiguration')
        ->set($key, $value);
    }
    $this->config('backstop_generator.backstopconfiguration')
      ->save();
    $redirect_url = Url::fromUri('internal:/admin/backstop_generator/backstop_configuration/view');
    $form_state->setRedirectUrl($redirect_url);
  }

}
