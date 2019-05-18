<?php

namespace Drupal\js_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\js_management\Entity\JavaScriptManaged;

class JsManagementForm extends ConfigFormBase {
  public function getFormId() {
    return 'js_management_form';
  }

  protected function getEditableConfigNames() {

  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $entities = \Drupal::service('js_management.managed_js')->getScripts();

    $form['about'] = [
      '#markup' => t('Uncheck scripts to disable loading site-wide.') . '<br>',
    ];

    foreach ($entities as $entity) {
      $form['script'][$entity->id()] = [
        '#type' => 'checkbox',
        '#default_value' => $entity->getLoad(),
        '#title' => $entity->getName() . " <a href=/{$entity->getName()}>" . t('view') . '</a>',
        '#description' => $entity->getVersion(),
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = \Drupal::service('js_management.managed_js')->getScripts();

    foreach ($form_state->getValues() as $key => $value) {
      if (!is_object($value) && is_int($key)) {
        if (!$value) {
          $entities[$key]->setLoad(FALSE);
        }
        else {
          $entities[$key]->setLoad(TRUE);
        }
        $entities[$key]->save();
      }
    }
    drupal_flush_all_caches();
  }
}
