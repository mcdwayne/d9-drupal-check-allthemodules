<?php

/**
 * Contains \Drupal\import_through_csv\Controller\ImportController.
 */
namespace Drupal\import_through_csv\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\import_through_csv\EntityCreateUpdate;
use Drupal\import_through_csv\EntityTypeFetch;

class ImportController extends FormBase {

  public  function getFormId() {
    // TODO: Implement getFormId() method.
    return 'import_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
      $entity['c'] = 'Content';
      $entity['t'] = 'Taxonomy';
      $entity['u'] = 'User';
      $form['#tree'] = TRUE;
      $form['entities'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('List Of Entities (Select the entity type whose content you want to create or update)'),
          '#prefix' => '<div id="entity-fieldset-wrapper">',
          '#suffix' => '</div>',
      ];

      $form['entities']['entityTypes'] = array(
          '#type' => 'radios',
          '#options' => $entity,
          '#ajax' => [
              'event' => 'change',
              'callback' => '::fetchEntityList',
              'wrapper' => 'entity-fieldset-wrapper',
          ],
      );
      if(!empty($form_state->getValue('entities'))) {
          $entityType = $form_state->getValue('entities');
          if($entityType['entityTypes'] == 'c') {
              $contentTypeObject = new EntityTypeFetch();
              $contentType = $contentTypeObject->fetchEntity('node_type');
              $contentTypeList = array();
              foreach ($contentType as $key => $value) {
                  $contentTypeList[$key] = $value;
              }
              $form['entities']['entityList'] = array(
                  '#type' => 'radios',
                  '#options' => $contentTypeList,
                  '#title' => $this->t('Content Type'),
                  '#description' => $this->t('Select a content type whose content you want to create. If it refers to any entity,
                                        the content of that entity will automatically be created once the csv file is uploaded.
                                        The title of the columns, of your csv file must match with the machine name of the fields. For eg:- If
                                        the machine name of the field is field_book, the title must also be field_book.')
              );
          }
          elseif($entityType['entityTypes'] == 't') {
              $taxonomyTermObject = new EntityTypeFetch();
              $taxonomyTerm = $taxonomyTermObject->fetchEntity('taxonomy_term');
              $taxonomyTermList = array();
              foreach ($taxonomyTerm as $key => $value) {
                  $taxonomyTermList[$key] = $value;
              }
              $form['entities']['entityList'] = array(
                  '#type' => 'radios',
                  '#options' => $taxonomyTermList,
                  '#title' => $this->t('Taxonomy Term'),
                  '#description' => $this->t('Select a taxonomy you want to create.')
              );
          }

          }
          $form['update'] = array(
              '#type' =>'checkbox',
              '#title' => 'Update',
              '#description' => 'Check Update if you want to update any existing content. Update only works on existing content.'
          );
          $form['csv_file'] = array(
              '#type' => 'managed_file',
              '#upload_location' => 'public://csv',
              '#title' => 'CSV File',
              '#upload_validators' => array(
                  'file_validate_extensions' => array('csv')),
          );
          $form['actions']['#type'] = 'actions';
          $form['actions']['submit'] = array(
              '#type' => 'submit',
              '#value' => $this->t('Save'),
              '#button_type' => 'primary',
          );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
      $UserInput = $form_state->getUserInput();
      $update = $form_state->getValue('update');
      $csvFile = $form_state->getValue('csv_file');
      $entityList = $UserInput['entities']['entityList'];
      $entityCreateObject = new EntityCreateUpdate();
      $entityCreateObject->csvParserList($csvFile[0], $entityList, $update, $UserInput['entities']['entityTypes']);
  }

  public function fetchEntityList(array &$form, FormStateInterface &$form_state) {
      $form_state->setRebuild(TRUE);
      return $form['entities'];
  }

 }
