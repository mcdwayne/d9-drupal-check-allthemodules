<?php

namespace Drupal\dea_magic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\dea\SolutionInterface;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\user\UserInterface;

class EntityReferenceSolution implements SolutionInterface {
  /**
   * @var UserInterface $account
   */
  protected $account;

  /**
   * @var FieldDefinitionInterface $field
   */
  protected $field;

  /**
   * @var EntityInterface $target
   */
  protected $target;

  /**
   * {@inheritdoc}
   */
  public function __construct(UserInterface $account, EntityInterface $target, FieldDefinitionInterface $field) {
    $this->account = $account;
    $this->target = $target;
    $this->field = $field;
  }

  /**
   * {@inheritdoc}
   */
  function __toString() {
    return $this->applyDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDescription() {
    return t('Add %target to %user\'s %field.', [
      '%user' => $this->account->label(),
      '%target' => $this->target->label(),
      '%field' => $this->field->getLabel(),
    ])->render();
  }

  /**
   * {@inheritdoc}
   */
  public function revokeDescription() {
    return t('Remove %target from %user\'s %field.', [
      '%user' => $this->account->label(),
      '%target' => $this->target->label(),
      '%field' => $this->field->getLabel(),
    ])->render();
  }

  public function isApplied() {
    return in_array($this->target, $this->account->get($this->field->getName())->referencedEntities());
  }


  /**
   * {@inheritdoc}
   */
  public function apply() {
    $this->account->{$this->field->getName()}[] = $this->target;
    $this->account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function revoke() {
    $items = $this->account->{$this->field->getName()}->getValue();
    $this->account->{$this->field->getName()} = array_filter($items, function ($item) {
      return $this->target->id() != $item['target_id'];
    });
    $this->account->save();
  }

}