<?php
namespace Drupal\cloudwords;

/**
 * Represents a project resource in Cloudwords. A project is the central resource in Cloudwords, as it
 * represents an initiative to translate some content. It contains information, both necessary and optional,
 * to define a project's requirements, such as the content's source language and requested target languages
 * to translate into.
 *
 * @author Douglas Kim <doug@cloudwords.com>
 * @since 1.0
 */
class CloudwordsProject {

  protected $id;
  protected $name;
  protected $description;
  protected $notes;
  protected $poNumber;
  protected $intendedUse;
  protected $department;
  protected $sourceLanguage;
  protected $targetLanguages;
  protected $status;
  protected $bidDueDate;
  protected $deliveryDueDate;
  protected $createdDate;
  protected $bidSelectDeadlineDate;
  protected $amount;
  protected $path;
  protected $params;

  /**
   * Constructor used to create a Cloudwords project
   *
   * - id: int The project id
   * - name: string The project name
   * - description: string The project description
   * - notes: string The project notes
   * - poNumber: string The project purchase order number
   * - intendedUse: int The project intended use unique identifier
   * - department: int The project intended use unique identifier
   * - sourceLanguage: string The language code for the source language
   * - targetLanguages: array The language codes for target languages
   * - status: array The project status code and display name
   * - bidDueDate: string The project bid due date
   * - deliveryDueDate: string The project delivery due date
   * - createdDate: string The project created date
   * - bidSelectDeadlineDate: string The project bid selection deadline date
   * - amount: int The amount or cost associated with this project
   * - path: string The api url to retrieve project metadata
   *
   * @param array $params The parameters used to initialize a project instance
   */
  public function __construct($params) {
    $this->params = $params;
    if (isset($params['id'])) {
      $this->id = $params['id'];
    }
    if (isset($params['name'])) {
      $this->name = $params['name'];
    }
    if (isset($params['description'])) {
      $this->description = $params['description'];
    }
    if (isset($params['notes'])) {
      $this->notes = $params['notes'];
    }
    if (isset($params['poNumber'])) {
      $this->poNumber = $params['poNumber'];
    }
    if (isset($params['intendedUse'])) {
      $this->intendedUse = $params['intendedUse'];
    }
    if (isset($params['department'])) {
      $this->department = $params['department']['id'];
    }
    if (isset($params['sourceLanguage'])) {
      $this->sourceLanguage = $params['sourceLanguage'];
    }
    if (isset($params['targetLanguages'])) {
      $this->targetLanguages = $params['targetLanguages'];
    }
    if (isset($params['status'])) {
      $this->status = $params['status'];
    }
    if (isset($params['bidDueDate'])) {
      $this->bidDueDate = $params['bidDueDate'];
    }
    if (isset($params['deliveryDueDate'])) {
      $this->deliveryDueDate = $params['deliveryDueDate'];
    }
    if (isset($params['createdDate'])) {
      $this->createdDate = $params['createdDate'];
    }
    if (isset($params['bidSelectDeadlineDate'])) {
      $this->bidSelectDeadlineDate = $params['bidSelectDeadlineDate'];
    }
    if (isset($params['amount'])) {
      $this->amount = $params['amount'];
    }
    if (isset($params['path'])) {
      $this->path = $params['path'];
    }
  }

  public function getParams() {
    return $this->params;
  }

  public function setParams($params) {
    $this->params = $params;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getName() {
    return $this->name;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function getNotes() {
    return $this->notes;
  }

  public function setNotes($notes) {
    $this->notes = $notes;
  }

  public function getPoNumber() {
    return $this->poNumber;
  }

  public function setPoNumber($poNumber) {
    $this->poNumber = $poNumber;
  }

  public function getIntendedUse() {
    return $this->transformIntendedUse($this->intendedUse);
  }

  public function getDepartment() {
    return $this->department;
  }

  public function setIntendedUse($intendedUse) {
    $this->intendedUse = $intendedUse;
  }

  public function getSourceLanguage() {
    return $this->transformSourceLanguage($this->sourceLanguage);
  }

  public function setSourceLanguage($sourceLanguage) {
    $this->sourceLanguage = $sourceLanguage;
  }

  public function getTargetLanguages() {
    return $this->transformTargetLanguages($this->targetLanguages);
  }

  public function setTargetLanguages($targetLanguages) {
    $this->targetLanguages = $targetLanguages;
  }

  public function getStatus() {
    return $this->transformProjectStatus($this->status);
  }

  public function setStatus($status) {
    $this->status = $status;
  }

  public function getBidDueDate() {
    return $this->bidDueDate;
  }

  public function setBidDueDate($bidDueDate) {
    $this->bidDueDate = $bidDueDate;
  }

  public function getDeliveryDueDate() {
    return $this->deliveryDueDate;
  }

  public function setDeliveryDueDate($deliveryDueDate) {
    $this->deliveryDueDate = $deliveryDueDate;
  }

  public function getCreatedDate() {
    return $this->createdDate;
  }

  public function setCreatedDate($createdDate) {
    $this->createdDate = $createdDate;
  }

  public function getBidSelectDeadlineDate() {
    return $this->bidSelectDeadlineDate;
  }

  public function setBidSelectDeadlineDate($bidSelectDeadlineDate) {
    $this->bidSelectDeadlineDate = $bidSelectDeadlineDate;
  }

  public function getAmount() {
    return $this->amount;
  }

  public function setAmount($amount) {
    $this->amount = $amount;
  }

  public function getPath() {
    return $this->path;
  }

  public function setPath($path) {
    $this->path = $path;
  }

  protected function transformSourceLanguage($sourceLanguage) {
    return new CloudwordsLanguage($sourceLanguage);
  }

  protected function transformTargetLanguages($targetLanguages) {
    $languages = [];
    foreach ($targetLanguages as $targetLanguage) {
      $languages[] = new CloudwordsLanguage($targetLanguage);
    }
    return $languages;
  }

  protected function transformIntendedUse($intendedUse) {
    return new CloudwordsIntendedUse($intendedUse);
  }

  protected function transformProjectStatus($status) {
    return new CloudwordsProjectStatus($status);
  }

}
