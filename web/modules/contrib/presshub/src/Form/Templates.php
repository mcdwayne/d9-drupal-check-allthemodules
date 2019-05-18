<?php

namespace Drupal\presshub\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\presshub\PresshubHelper;

/**
 * Presshub templates form.
 */
class Templates extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'presshub_templates';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $presshub = new PresshubHelper();

    $form['types'] = [
      '#type'        => 'table',
      '#header'      => [
        $this->t('Entity Type'),
        $this->t('Template'),
      ],
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
    ];

    $entity_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($entity_types as $type => $info) {
      $templates = [];
      $templates[] = $this->t('- none -');
      foreach ($presshub->getTemplates() as $template => $data) {
        if (in_array($type, $data['entity_types'])) {
          $templates[$template] = $data['name'];
        }
      }
      $form['types'][$type]['type'] = [
        '#type'   => 'markup',
        '#markup' => $info->label(),
      ];
      $template = db_select('presshub_templates', 't')
        ->fields('t', ['template'])
        ->condition('t.entity_type', $type)
        ->execute()
        ->fetchField();
      if (!empty($templates)) {
        $form['types'][$type]['template'] = [
          '#type'          => 'select',
          '#options'       => $templates,
          '#default_value' => $template,
        ];
      }
      else {
        $form['types'][$type]['template'] = [
          '#type'    => 'select',
          '#options' => ['' => $this->t('- none -')],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    drupal_set_message($this->t('The configuration options have been saved.'));
    foreach ($values['types'] as $entity_type => $data) {
      if (!empty($data['template'])) {
        db_merge('presshub_templates')
          ->key(['entity_type' => $entity_type])
          ->fields([
              'entity_type' => $entity_type,
              'template'    => $data['template'],
          ])
          ->execute();
      }
      else {
        db_delete('presshub_templates')
          ->condition('entity_type', $entity_type)
          ->execute();
      }
    }
  }

}
