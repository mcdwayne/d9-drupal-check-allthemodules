<?php

namespace Drupal\simpleads;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Groups utility class.
 */
class Groups {

  use StringTranslationTrait;

  protected $id;
  protected $user;
  protected $name;
  protected $description;
  protected $options;
  protected $created_at;
  protected $changed_at;

  public function __construct() {
    $this->user = \Drupal::currentUser();
  }

  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  public function getId() {
    return $this->id;
  }

  public function setGroupName($name) {
    $this->name = $name;
    return $this;
  }

  public function getGroupName() {
    return $this->name;
  }

  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setOptions(array $options = []) {
    $this->options = json_encode($options);
    return $this;
  }

  public function getOptions($decode = FALSE) {
    return $decode ? json_decode($this->options, TRUE) : $this->options;
  }

  public function setCreatedAt($created_at) {
    $this->created_at = $created_at;
    return $this;
  }

  public function getCreatedAt() {
    return !empty($this->created_at) ? $this->created_at : time();
  }

  public function setChangedAt($changed_at) {
    $this->changed_at = $changed_at;
    return $this;
  }

  public function getChangedAt() {
    return !empty($this->changed_at) ? $this->changed_at : time();
  }

  public function load() {
    if ($id = $this->getId()) {
      $record = db_select('simpleads_groups', 's')
        ->fields('s')
        ->condition('s.id', $id)
        ->execute()
        ->fetchObject();
      $item = (new self())
        ->setId($id)
        ->setGroupName($record->name)
        ->setDescription($record->description)
        ->setOptions(!empty($record->options) ? json_decode($record->options, TRUE) : [])
        ->setCreatedAt($record->created_at)
        ->setChangedAt($record->changed_at);
      return $item;
    }
    return $this;
  }

  public function loadAll() {
    $items = [];
    $result = db_select('simpleads_groups', 's')
      ->fields('s')
      ->orderBy('s.changed_at', 'DESC')
      ->execute();
    foreach ($result as $row) {
      $items[] = $this->setId($row->id)->load();
    }
    return $items;
  }

  public function loadAsOptions() {
    $options = [];
    $options[''] = $this->t('- None -');
    foreach ($this->loadAll() as $item) {
      $options[$item->getId()] = $item->getGroupName();
    }
    return $options;
  }

  public function save() {
    $fields = [
      'uid'         => $this->user->id(),
      'name'        => $this->getGroupName(),
      'description' => $this->getDescription(),
      'options'     => $this->getOptions(),
      'created_at'  => $this->getCreatedAt(),
      'changed_at'  => $this->getChangedAt(),
    ];
    if ($id = $this->getId()) {
      $query = db_update('simpleads_groups')
        ->fields($fields)
        ->condition('id', $id);
      drupal_set_message($this->t('Group successfully updated.'));
    }
    else {
      $query = db_insert('simpleads_groups')
        ->fields($fields);
      drupal_set_message($this->t('New Group successfully created.'));
    }
    $query->execute();
  }

  public function delete() {
    if ($id = $this->getId()) {
      $group = $this->load();
      db_delete('simpleads_groups')
        ->condition('id', $id)
        ->execute();
      drupal_set_message($this->t('Group <em>@name</em> successfully delete.', ['@name' => $group->getGroupName()]));
    }
  }

}
