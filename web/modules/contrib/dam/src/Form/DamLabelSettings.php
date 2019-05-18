<?php

namespace Drupal\dam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dam\Controller\DamController;
use Drupal\dam\Entity\FTPAccount;
use Drupal\dam\Entity\FileLabel;
use Drupal\dam\Entity\FileLabelGroup;

/**
* FTP settings for the Digital Assets manager.
*
* @package Drupal\dam\Form
*/
class DamLabelSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dam_label_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dam.global_style',
      'dam.label_settings',
    ];
  }

  /**
  * Helper function that returns all of the current File Label groups.
  */
  protected function getFileLabels($file_label_group_id) {
   $database = \Drupal::database();
   $entity_storage = \Drupal::entityTypeManager()->getStorage('file_label');
   $select = $database->select('file_label', 'flg');
   $select->fields('flg', ['id']);
   $select->condition('group_name', $file_label_group_id);
   $data = $select->execute();
   while($record = $data->fetch(\PDO::FETCH_ASSOC)) {
     yield $entity_storage->load($record['id']);
   }
  }

  /**
  * Helper function that returns all of the current File Labels.
  */
  protected function getFileLabelGroups() {
   $database = \Drupal::database();
   $entity_storage = \Drupal::entityTypeManager()->getStorage('file_label_group');
   $select = $database->select('file_label_group', 'flg');
   $select->fields('flg', ['id']);
   $data = $select->execute();
   while($record = $data->fetch(\PDO::FETCH_ASSOC)) {
     yield $entity_storage->load($record['id']);
   }
  }

  /**
  * Helper function that returns the invers color.
  */
  private function color_inverse($color){
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6){ return '000000'; }
    $rgb = '';
    for ($x=0;$x<3;$x++){
        $c = 255 - hexdec(substr($color,(2*$x),2));
        $c = ($c < 0) ? 0 : dechex($c);
        $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
    }
    return '#'.$rgb;
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'dam_labels';
    $form['#attached'] = array(
      'library' => array(
        'dam/file_label_settings',
        'dam/global_style',
      ),
    );

    $form['file_label_group'] = [
      '#type' => 'fieldset',
      '#title' => t('Add New Group'),
      '#tree' => TRUE,
    ];

    $form['file_label_group']['name'] = [
      '#type' => 'textfield',
      '#title' => t('Group Name')
    ];

    $form['file_label_group']['save'] = [
      '#type' => 'submit',
      '#value' => t('Add Label Group'),
      '#validate' => ['::validateNewLabelGroup'],
      '#submit' => ['::saveNewLabelGroup']
    ];

    $form['groups'] = [
      '#tree' => TRUE,
    ];


    foreach($this->getFileLabelGroups() as $id => $entity) {
      $form['groups'][$entity->id()] = [
        '#type' => 'fieldset',
        '#title' => $entity->label()
      ];

      // Get all of the labels for this group and display them.
      $form['groups'][$entity->id()]['labels'] = [
        '#type' => 'fieldset',
        '#title' => $entity->label()
      ];

      foreach($this->getFileLabels($entity->id()) as $id => $labelEntity) {

        $color = $labelEntity->get('color')->getValue()[0]['value'];
        $color_inverse = $this->color_inverse($color);
        $title = $labelEntity->get('title')->getValue()[0]['value'];

        $form['groups'][$entity->id()]['labels'][$labelEntity->id()] = [
          '#type' => 'fieldset',
          '#attributes' => [
            'style' => 'background-color: ' . $color .'; padding:10px; color:' . $color_inverse .' !important;',
            'class' => 'label-wrapper',
            'id' => 'file-label-' . $labelEntity->id()
          ]
        ];

        $form['groups'][$entity->id()]['labels'][$labelEntity->id()]['label'] = [
          '#type' => 'markup',
          '#markup' => '<div>' . $title . '</div>'
        ];

        $form['groups'][$entity->id()]['labels'][$labelEntity->id()]['remove'] = [
          '#type' => 'submit',
          '#value' => t('Delete'),
          '#submit' => ['::deleteLabel'],
          '#id' => 'label-delete-' . $labelEntity->id()
        ];
      }

      if (count($form['groups'][$entity->id()]['labels']) < 3) {
        $form['groups'][$entity->id()]['labels']['empty'] = [
          '#type' => 'markup',
          '#markup' => t('There are currently no labels for this group.')
        ];
      }

      $form['groups'][$entity->id()]['add_label'] = [
        '#type' => 'fieldset',
        '#attributes' => [
          'class' => 'label-create-wrapper'
        ]
      ];

      $form['groups'][$entity->id()]['add_label']['title'] = [
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#description' => t('Enter the name for the new label.')
      ];

      $form['groups'][$entity->id()]['add_label']['color'] = [
        '#type' => 'color',
        '#title' => t('Color'),
        '#description' => t('Select the color for this label.')
      ];

      $form['groups'][$entity->id()]['add_label']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save label'),
        '#validate' => ['::validateLabel'],
        '#submit' => ['::submitLabel'],
        '#id' => 'label-create-' . $entity->id()
      ];

      $form['groups'][$entity->id()]['delete'] = [
        '#type' => 'submit',
        '#id' => 'file-label-group-delete-' . $entity->id(),
        '#submit' => ['::fileGroupLabelDelete'],
        '#value' => t('Delete Label Group')
      ];

    }

    return $form;
  }

  /**
  * - Validates a submitted label.
  */
  public function validateLabel($form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
    $file_label_group = explode('-', $trigger['#id'])[2];

    // Make sure the label has a name.
    if (strlen($values['groups'][$file_label_group]['add_label']['title']) < 1 || $values['groups'][$file_label_group]['add_label']['title'] == "") {
      $form_state->setError($form['groups'][$file_label_group]['add_label']['title'], t('Label must have a name to be created.'));
    }

    if (strlen($values['groups'][$file_label_group]['add_label']['color']) < 1 || $values['groups'][$file_label_group]['add_label']['color'] == "") {
      $form_state->setError($form['groups'][$file_label_group]['add_label']['color'], t('Label must have a color to be created.'));
    }

  }

  /**
  * - Saves a submitted label.
  */
  public function submitLabel($form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
    $file_label_group = explode('-', $trigger['#id'])[2];


    $entity = FileLabel::create([
      'title'  => $values['groups'][$file_label_group]['add_label']['title'],
      'color'  => $values['groups'][$file_label_group]['add_label']['color'],
      'group_name' => $file_label_group
    ]);
    $entity->save();

  }


  /**
  * - Submit callback for deleting a label entity.
  */
  public function deleteLabel($form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
    $file_label_id = explode('-', $trigger['#id'])[2];
    entity_delete_multiple('file_label', [ $file_label_id ]);

  }

  /**
  * Submit callback for deleting a label group.
  */
  public function fileGroupLabelDelete($form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
    $file_label_group = explode('-', $trigger['#id'])[4];
    entity_delete_multiple('file_label_group', [ $file_label_group ]);
  }

  /**
  * - Validation handler for creating new File label groups.
  */
  public function validateNewLabelGroup($form, FormStateInterface &$form_state) {

    // Make sure the name is not blank.
    $values = $form_state->getValues();
    if (strlen($values['file_label_group']['name']) < 1 || $values['file_label_group']['name'] == '') {
      $form_state->setError($form['file_label_group']['name'], t('Label group must have a name to be created.'));
    }

    // Make sure that the name has not been created already
    $ids = \Drupal::entityQuery('file_label_group')
      ->condition('name', $values['file_label_group']['name'])
      ->execute();
    if(count($ids) > 0) {
      $form_state->setError($form['file_label_group']['name'], t('Label group names must be unique.'));
    }

  }

  /**
  * - Submit handler for creating new Label Groups.
  */
  public function saveNewLabelGroup($form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $entity = FileLabelGroup::create(['name' => $values['file_label_group']['name']]);
    $entity->save();
    drupal_set_message('New File Label group %name has been created.', ['%name' => $values['file_label_group']['name']]);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('dam.label_settings');

    $this->config('dam.label_settings')
      ->set('vocabularies', $form_state->getValue('vocabularies'))
      ->save();
  }

}
