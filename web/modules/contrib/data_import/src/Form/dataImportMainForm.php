<?php
/**
 * @file
 * Contains \Drupal\data_import\Form\dataImportMainForm.
 */
 
namespace Drupal\data_import\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\data_import\Controller;

class dataImportMainForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'data_import_main_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $user;

    $linkCreate = \Drupal::l(t('Create New Import'), \Drupal\Core\Url::fromRoute('data_import.create_importer'));
    $form['create_new_import'] = array(
      '#markup' => '<ul class="action-links"><li>'.$linkCreate.'</li></ul>',
      '#weight' => 1,
    );
  
    // Load all importers
    $importers = data_import_load_all_importers();
  
    if (!empty($importers)) {
      $form['importers'] = array(
        '#weight' => 2,
        '#tree' => TRUE,
        '#theme' => 'data_import_table_form',
      );
  
      foreach ($importers as $key => $importer) {
        $form['importers'][$key] = array(
          '#type' => 'checkbox',
          '#importer_name' => $importer['name'],
          '#default_value' => $importer['active']
        );
      }
  
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#weight' => 3,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $importersState = $form_state->getValue('importers');
    if (!empty($importersState)) {
      foreach ($importersState as $key => $value) {
        $importer = ['importer_id' => $key, 'active' => $value];
        data_importer_save($importer);
      }
    }
  
    drupal_set_message(t('The configuration have been saved.'));
  }


}