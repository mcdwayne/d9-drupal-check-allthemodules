<?php

namespace Drupal\simpleads;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ads utility class.
 */
class Ads extends UtilityBase {

  use StringTranslationTrait;

  protected $manager_name = 'plugin.manager.simpleads.types';
  protected $id;
  protected $user;
  protected $name;
  protected $description;
  protected $type;
  protected $group;
  protected $campaign;
  protected $options;
  protected $status;
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

  public function setAdName($name) {
    $this->name = $name;
    return $this;
  }

  public function getAdName() {
    return $this->name;
  }

  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  public function getType() {
    return $this->type;
  }

  public function setGroup(Groups $group) {
    $this->group = $group;
    return $this;
  }

  public function getGroup() {
    return $this->group;
  }

  public function setCampaign(Campaigns $campaign) {
    $this->campaign = $campaign;
    return $this;
  }

  public function getCampaign() {
    return $this->campaign;
  }

  public function setOptions(array $options = []) {
    $this->options = json_encode($options);
    return $this;
  }

  public function getOptions($decode = FALSE) {
    return $decode ? json_decode($this->options, TRUE) : $this->options;
  }

  public function setStatus($status = 1) {
    $this->status = $status;
    return $this;
  }

  public function getStatus() {
    return $this->status;
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
      $record = db_select('simpleads', 's')
        ->fields('s')
        ->condition('s.id', $id)
        ->execute()
        ->fetchObject();
      $item = (new self())
        ->setId($id)
        ->setAdName($record->name)
        ->setDescription($record->description)
        ->setType($record->type)
        ->setGroup((new Groups)->setId($record->group_id)->load())
        ->setCampaign((new Campaigns)->setId($record->campaign_id)->load())
        ->setOptions(!empty($record->options) ? json_decode($record->options, TRUE) : [])
        ->setStatus($record->status)
        ->setCreatedAt($record->created_at)
        ->setChangedAt($record->changed_at);
      return $item;
    }
    return $this;
  }

  public function loadAll() {
    $items = [];
    $result = db_select('simpleads', 's')
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
      $options[$item->getId()] = $item->getAdName();
    }
    return $options;
  }

  public function save() {
    $fields = [
      'uid'         => $this->user->id(),
      'name'        => $this->getAdName(),
      'description' => $this->getDescription(),
      'type'        => $this->getType(),
      'group_id'    => !empty($this->group->getId()) ? $this->group->getId() : NULL,
      'campaign_id' => !empty($this->campaign->getId()) ? $this->campaign->getId() : NULL,
      'options'     => $this->getOptions(),
      'status'      => $this->getStatus(),
      'created_at'  => $this->getCreatedAt(),
      'changed_at'  => $this->getChangedAt(),
    ];
    if ($id = $this->getId()) {
      $query = db_update('simpleads')
        ->fields($fields)
        ->condition('id', $id);
      drupal_set_message($this->t('Advertisement successfully updated.'));
    }
    else {
      $query = db_insert('simpleads')
        ->fields($fields);
      drupal_set_message($this->t('New advertisement successfully created.'));
    }
    $query->execute();
  }

  public function delete() {
    if ($id = $this->getId()) {
      $group = $this->load();
      db_delete('simpleads')
        ->condition('id', $id)
        ->execute();
      drupal_set_message($this->t('Advertisement <em>@name</em> successfully delete.', ['@name' => $group->getAdName()]));
    }
  }

}
