<?php

namespace Drupal\ptoc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures which paragraph types generate the Table of Contents.
 */
class PtocConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ptoc_admin_settings_type_enable';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $settings = \Drupal::config('ptoc.settings');
    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debug mode.'),
      '#description' => $this->t('Put a border around each paragraph, both in the ToC and the body of the page.'),
      '#default_value' => $settings->get('debug'),
    ];

    $form['usage'] = [
      '#type' => 'markup',
      '#markup' => $this->t('First, select which paragraph types should have the Table of Contents (ToC) display mode enabled and submit the form. Then, for each selected type, choose the fields that should appear in the ToC display mode. If the ToC display mode is enabled, then an id attribute will be added to the default display mode.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['caveat'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Most enabled types should be configured to show a single text field. Types that serve as containers for sub-paragraphs can also have the reference field displayed.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    if (!$paragraph_types = \Drupal::entityManager()->getBundleInfo('paragraph')) {
      $form['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No paragraph types have been defined. Create some at <a href=":paragraph">Paragraphs types</a>.', [':paragraph' => \Drupal::url('entity.paragraphs_type.collection')]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }
    else {
      $form['ptoc'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configure paragraph types.'),
      ];

      foreach ($paragraph_types as $bundle => $setting) {
        $display = \Drupal::config("core.entity_view_display.paragraph.$bundle.ptoc");
        $status = !empty($display->get('status'));

        $form['ptoc'][$bundle] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Configure %type', ['%type' => $setting['label']]),
        ];
        $form['ptoc'][$bundle]['enable'] = [
          '#type' => 'checkbox',
          '#default_value' => $status,
          '#title' => $this->t('Enable %type', ['%type' => $setting['label']]),
        ];

        if ($status) {
          $properties = $display->get();
          $enabled = array_keys($properties['content']);
          $disabled = array_keys($properties['hidden']);
          $defaults = array_combine($enabled, $enabled);
          $options = $defaults + array_combine($disabled, $disabled);
          $form['ptoc'][$bundle]['fields'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Select fields'),
            '#options' => $options,
            '#default_value' => $defaults,
          ];
        }
      }

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Configure Paragraph Types'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = \Drupal::configFactory()->getEditable('ptoc.settings');
    $settings->set('debug', $form_state->getValue('debug'))->save();

    $bundles = array_keys($form_state->getValue('ptoc'));
    $config_ids = [];
    foreach ($bundles as $bundle) {
      $config_ids[] = "paragraph.$bundle.ptoc";
    }
    $displays = \Drupal::entityManager()
      ->getStorage('entity_view_display')
      ->loadMultiple($config_ids);
    $options = [
      'label' => 'visually_hidden',
      'settings' => ['view_mode' => 'ptoc'],
    ];

    foreach ($form_state->getValue('ptoc') as $bundle => $settings) {
      $status = !empty($settings['enable']);
      if (isset($displays["paragraph.$bundle.ptoc"])) {
        // There is already a config object for this display.
        /** @var Drupal\Core\Entity\Entity\EntityViewDisplay $display */
        $display = $displays["paragraph.$bundle.ptoc"];
        $display->set('status', $status);
        if ($status) {
          foreach ($settings['fields'] as $field => $enabled) {
            if (strpos($field, 'field_') !== 0) {
              continue;
            }
            if ($enabled && $display->getComponent($field) === NULL) {
              $display->setComponent($field, $options);
            }
            if (!$enabled && $display->getComponent($field) !== NULL) {
              $display->removeComponent($field);
            }
          }
        }
        $display->save();
      }
      elseif ($status) {
        // The display mode's config object has not been created.
        $display = \Drupal::entityManager()
          ->getStorage('entity_view_display')
          ->load("paragraph.$bundle.default")
          ->createCopy('ptoc');
        foreach (array_keys($display->getComponents()) as $field) {
          if (strpos($field, 'field_') !== 0) {
            continue;
          }
          $display->removeComponent($field);
        }
        $display->save();
      }
    }
  }

}
