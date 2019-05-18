<?php

namespace Drupal\import_through_csv;

use Drupal\Core\Entity;
use Drupal\Core\Field;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

 /**
  * Class EntityCreateUpdate
  * @package Drupal\import_through_csv.
  *         Fetches the imported csv file, parses it, prepares an associative array(containing each row in the file)
  *         and creates entity as well as references entity.
  */

class EntityCreateUpdate {

    public function referenceFieldSet($fieldInfo, $csvValue, $field, $list, $update) {
        $flag = 0;
        $fieldSettings = $fieldInfo->getSettings();
        $targetEntityType = explode(':', $fieldSettings['handler']);
        $targetEntities = array_keys($fieldSettings['handler_settings']['target_bundles']);
        if ($targetEntityType[1] == 'node') {
            foreach ($targetEntities as $targetEntityKey => $targetEntity) {
                $query = \Drupal::entityQuery('node')->condition('type', $targetEntity)->condition('title', $csvValue[$field], 'CONTAINS');
                $nid = $query->execute();
                if (sizeof($nid) == 0) {
                    $flag = 0;
                }
                else {
                    $flag = 1;
                    break;
                }
            }
            if ($flag == 0 && $update == 0) {
                $targetIdRecords = array_values($this->createEntity($targetEntity[0], $csvValue, $csvValue[$field]));
                $list[$field]['target_id'] = $targetIdRecords[0];
            }
            else {
                $id = array_values($nid);
                if($update == 0) {
                    $list[$field]['target_id'] = $id[0];
                }
                else {
                    $list->$field->target_id = $id[0];
                }
            }
        }
        elseif ($targetEntityType[1] == 'taxonomy_term') {
            $termName = $csvValue[$field];
            foreach ($targetEntities as $targetEntityKey => $targetEntityValue) {
                    $tid = $this->termExistOrCreate($targetEntityValue, $termName, $csvValue);
                if ($update == 0) {
                    $list[$field]['target_id'] = $tid;
                }
                else {
                    $list->$field->target_id = $tid;
                }
            }
        }
        else {
            $userId = $this->userExistOrCreate($csvValue, $csvValue[$field]);
            if($update == 0) {
                $list[$field]['target_id'] = $userId;
            }
            else {
                $list->$field->target_id = $userId;
            }
        }
        return $list;
    }
    public function multivalueFieldSet($csvValue,$field,$list,$fieldType, $fieldInfo, $update) {
        $flag = 0;
        if($fieldType == 'entity_reference') {
            $fieldSettings = $fieldInfo->getSettings();
            $targetEntities = array_keys($fieldSettings['handler_settings']['target_bundles']);
            $targetEntityType = explode(':', $fieldSettings['handler']);
            if ($targetEntityType[1] == 'node') {
                if (array_key_exists($field, $csvValue)) {
                    if (strpos($csvValue[$field], '|') !== false) {
                        $explodeCsvField = explode('|', $csvValue[$field]);
                        foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                            foreach ($targetEntities as $targetEntityKey => $targetEntity) {
                                $query = \Drupal::entityQuery('node')->condition('type', $targetEntity)->condition('title', $multiValue, 'CONTAINS');
                                $nid = $query->execute();
                                if (sizeof($nid) == 0) {
                                    $flag = 0;
                                }
                                else {
                                    $flag = 1;
                                    break;
                                }
                            }
                            if ($flag == 0 && $update == 0) {
                                $targetIdRecords = array_values($this->createEntity($targetEntity, $csvValue, $multiValue));
                                $list[$field][$multiValueKey]['target_id'] = $targetIdRecords[0];
                            }
                            else {
                                $id = array_values($nid);
                                if($update == 0) {
                                    $list[$field][$multiValueKey]['target_id'] = $id[0];
                                }
                                else {
                                    $list->$field[$multiValueKey] = ['target_id'=> $id[0]];
                                }
                            }
                        }
                    }
                    else {
                        $targetEntityValue = $csvValue[$field];
                        foreach ($targetEntities as $targetEntityKey => $targetEntity) {
                            $query = \Drupal::entityQuery('node')->condition('type', $targetEntity)->condition('title', $targetEntityValue, 'CONTAINS');
                            $nid = $query->execute();
                            if (sizeof($nid) == 0) {
                                $flag = 0;
                            }
                            else {
                                $flag = 1;
                                break;
                            }
                        }
                        if ($flag == 0 && $update == 0) {
                            $targetIdRecords = array_values($this->createEntity($targetEntity, $csvValue, $targetEntityValue));
                            $list[$field][0]['target_id'] = $targetIdRecords[0];
                        }
                        else {
                            $id = array_values($nid);
                            if($update == 0) {
                                $list[$field][0]['target_id'] = $id[0];
                            }
                            else {
                                $list->$field->target_id = $id[0];
                            }
                        }
                    }
                }
            }
            elseif ($targetEntityType[1] == 'taxonomy_term') {
                foreach ($targetEntities as $targetEntityKey => $targetEntity) {
                    if (array_key_exists($field, $csvValue)) {
                        if (strpos($csvValue[$field], '|') !== false) {
                            $explodeCsvField = explode('|', $csvValue[$field]);
                            foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                                $termName = $multiValue;
                                $tid = $this->termExistOrCreate($targetEntity, $termName, $csvValue);
                                if($update == 0) {
                                    $list[$field][$multiValueKey]['target_id'] = $tid;
                                }
                                else {
                                    $list->$field[$multiValueKey]= ['target_id' => $tid];
                                }
                            }
                        } else {
                            $termName = $csvValue[$field];
                            $tid = $this->termExistOrCreate($targetEntity, $termName, $csvValue);
                            if($update == 0) {
                                $list[$field]['target_id'] = $tid;
                            }
                            else {
                                $list->$field->target_id = $tid;
                            }
                        }
                    }
                }
            }
            else {
                if (array_key_exists($field, $csvValue)) {
                    if (strpos($csvValue[$field], '|') !== false) {
                        $explodeCsvField = explode('|', $csvValue[$field]);
                        foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                            $userId = $this->userExistOrCreate($csvValue, $multiValue);
                            if($update == 0) {
                                $list[$field][$multiValueKey]['target_id'] = $userId;
                            }
                            else {
                                $list->$field[$multiValueKey] = ['target_id' => $userId];
                            }
                        }
                    } else {
                        $userId = $this->userExistOrCreate($csvValue, $csvValue[$field]);
                        if($update == 0) {
                            $list[$field]['target_id'] = $userId;
                        }
                        else {
                            $list->$field->target_id = $userId;
                        }
                    }
                }
            }

        }
        else {
            if (array_key_exists($field, $csvValue)) {
                if (strpos($csvValue[$field], '|') !== false) {
                    $explodeCsvField = explode('|', $csvValue[$field]);
                    foreach ($explodeCsvField as $multiValueKey => $multiValue) {
                        if($update == 0) {
                            $list[$field][$multiValueKey] = $multiValue;
                        }
                        else {
                            $list->$field[$multiValueKey]= $multiValue;
                        }
                    }
                }
                else {
                    if($update == 0) {
                        $list[$field] = $csvValue[$field];
                    }
                    else {
                        $list->$field = $csvValue[$field];
                    }
                }
            }
        }
        return $list;
    }
    /**
     * Checks whether a taxonomy term exist or not. If not then it creates
     * @param $targetEntity
     *    The name of the Taxonomy Vocabulary
     * @param $termName
     *    The name of the Taxonomy Term to be checked
     * @param $csvValue
     *    An array containing the csv file records
     * @return int
     *    tid of the term
     */
    public function termExistOrCreate($targetEntity, $termName, $csvValue) {
        $term = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['name' => $termName]);
        if (sizeof($term) == 0) {
            $termList['name'] = $termName;
            $termList['vid'] = $targetEntity;
            if (array_key_exists("parent", $csvValue)) {
                $parent = $csvValue['parent'];
                if($parent!= '') {
                    unset($csvValue['parent']);
                    $termParent = $this->termExistOrCreate($targetEntity, $parent, $csvValue);
                    $termList['parent'] = $termParent;
                }
                else {
                    $termList['parent'] = 0;
                }
            }
            $term = Term::create($termList);
            $term->save();
            $term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $termName]);
        }
        $termShift = array_shift($term);
        $tid = $termShift->get('tid')->value;
        return $tid;
    }

    /**
     * Checks whether user exist or not. If exists it creates and returns its uid.
     * @param $csvValue
     *    An array containing csv file records/row
     * @param $name
     *    Name of the user
     * @return int
     *    uid of the user.
     */
    public function userExistOrCreate($csvValue, $name) {
        $userRecord = user_load_by_name($name);
        if ($userRecord == FALSE) {
            $userField['name'] = $name;
            $userField['mail'] = $csvValue['mail'];
            $userField['pass'] = $csvValue['pass'];
            $userField['status'] = $csvValue['status'];
            $user = User::create($userField);
            $user->save();
            $userRecord = user_load_by_name($name);
            $uid = $userRecord->get('uid')->value;
        }
        else {
            $uid = $userRecord->get('uid')->value;
        }
        return $uid;
    }

    public function updateEntity($entity, $csvValue, $title, $entityType) {
        if($entityType == 'c') {
            $emptyArray[] = array();
            $query = \Drupal::entityQuery('node')->condition('type', $entity)->condition('title', $title, '=');
            $entityId = $query->execute();
        }
        elseif($entityType == 't') {
            $term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties(['name' => $title]);
            $termShift = array_shift($term);
            $entityId = $termShift->get('tid')->value;

        }
        else {
            $userRecord = user_load_by_name($title);
            $entityId = $userRecord->get('uid')->value;
        }
        if (sizeof($entityId) != 0) {
            if($entityType == 'c') {
                $id = array_values($entityId);
                $loadEntity = Node::load($id[0]);
                $fetchFieldObject = new Fetchfields();
                $fields = $fetchFieldObject->contentTypeFieldsFetch($entity);
                $entityTypeId = 'node';
            }
            elseif($entityType == 't') {
                $loadEntity = Term::load($entityId);
                $fields = array_keys($loadEntity->getFields(TRUE));
                $entityTypeId = 'taxonomy_term';
            }
            else {
                $loadEntity = $userRecord;
                $fields = array_keys($loadEntity->getFields(TRUE));
                $entityTypeId = 'user';
                $entity = 'user';
            }
            foreach ($fields as $index => $field) {
                if (array_key_exists($field, $csvValue)) {
                    $fieldStorageInfo = FieldStorageConfig::loadByName($entityTypeId, $field);
                    $fieldInfo = FieldConfig::loadByName($entityTypeId, $entity, $field);
                    if($field == 'mail' && $entityType == 'u') {
                        $loadEntity->set('mail', $csvValue['mail']);
                    }
                    if ($fieldInfo != NULL || $fieldStorageInfo != NULL) {
                        $fieldType = $fieldInfo->getType();
                        $fieldMultiple = $fieldStorageInfo->isMultiple();
                        if($fieldMultiple == 'TRUE') {
                            $loadEntity = $this->multivalueFieldSet($csvValue,$field,$loadEntity,$fieldType, $fieldInfo, 1);

                        }
                        else {
                            if($fieldType == 'entity_reference') {
                                $loadEntity = $this->referenceFieldSet($fieldInfo, $csvValue, $field, $loadEntity, 1);
                            }
                            else {
                                $loadEntity->set($field, $csvValue[$field]);
                            }
                        }
                    }
                }
            }
            $loadEntity->save();
        }
    }
    /**
     * Creates entity and references entity.
     * @param $entityType
     *   The bundle whose content is required to be created.
     * @param $csvValue
     *   An array containing the csv file records
     * @param $title
     *   Value for the title field of the selected content Type
     *
     * @return int
     *   Id of the created entity
     */
    public function createEntity($entityType, $csvValue, $title) {
        $fetchFieldObject = new Fetchfields();
        $fields = $fetchFieldObject->contentTypeFieldsFetch($entityType);
        $list['type'] = $entityType;
        foreach ($fields as $fieldId => $field) {
            $fieldStorageInfo = FieldStorageConfig::loadByName('node', $field);
            $list['title'] = $title;
            if ($fieldStorageInfo != NULL) {
                $fieldInfo = FieldConfig::loadByName('node', $entityType, $field);
                $fieldType = $fieldInfo->getType();
                $fieldMultiple = $fieldStorageInfo->isMultiple();
                if($fieldMultiple == 'TRUE') {
                    $list = $this->multivalueFieldSet($csvValue,$field,$list,$fieldType, $fieldInfo, 0);
                }
                else {
                    if($fieldType == 'entity_reference') {
                        $list = $this->referenceFieldSet($fieldInfo, $csvValue, $field, $list, 0);
                    }
                    else {
                        $list[$field] = $csvValue[$field];
                    }
                }
            }
        }
        $node = Node::create($list);
        $node->save();
        $query = \Drupal::entityQuery('node')->condition('type', $entityType)->condition('title', $title,'CONTAINS');
        $nid = $query->execute();
        return $nid;
    }

    /**
     * Fetches the csv file, parses it and prepares an associative array containing each row of the csv file.
     * @param $csvFileFid
     *   Fid of the csv file uploaded
     * @param $entity
     *   The $entity to be created / updated
     * @param $update
     *   The value of update
     */
    public function csvParserList($csvFileFid, $entity, $update, $entityType) {
        $file = \Drupal\file\Entity\File::load($csvFileFid);
        $path = $file->getFileUri();
        $csv = array_map('str_getcsv', file($path));
        foreach ($csv[0] as $headerId => $headerValue) {
            $headerTitle[] = $headerValue;
        }
        unset($csv[0]);
        $items = array();
        foreach ($csv as $id => $value) {
            foreach ($value as $key => $csvValue) {
                $items[$id][$headerTitle[$key]] = $csvValue;
            }
        }
        if($entityType == 'c')
        {
            $contentType[] = array();
            if($update == 0) {
                foreach ($items as $csvId => $csvValue) {
                    $title = $csvValue['title'];
                    $contentType = $this->createEntity($entity, $csvValue, $title);
                }
                drupal_set_message(t('Entities created'));
            }
            else {
                foreach ($items as $csvId => $csvValue) {
                    $title = $csvValue['title'];
                    $this->updateEntity($entity,$csvValue,$title,$entityType);
                }
                drupal_set_message(t('Entities updated'));
            }
        }
        elseif($entityType == 't') {
            $taxonomy[] = array();
            if($update == 0) {
                foreach ($items as $csvId => $csvValue) {
                    $taxonomy = $this->termExistOrCreate($entity, $csvValue['term'], $csvValue);
                }
                drupal_set_message(t('Taxonomy created'));
            }
            else {
                foreach ($items as $csvId => $csvValue) {
                    $this->updateEntity($entity,$csvValue,$csvValue['term'],$entityType);
                }
                drupal_set_message(t('Taxonomy updated'));
            }
        }
        else {
            $user[] = array();
            if($update == 0) {
                foreach ($items as $csvId => $csvValue) {
                    $name = $csvValue['name'];
                    $user = $this->userExistOrCreate($csvValue,$name);
                }
                drupal_set_message(t('User created'));
            }
            else {
                foreach ($items as $csvId => $csvValue) {
                    $this->updateEntity($entity,$csvValue,$csvValue['name'],$entityType);
                }
                drupal_set_message(t('User updated'));
            }
        }
    }

}
