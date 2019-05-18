<?php
namespace Drupal\dea\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemList;

class EntityOperationItemList extends FieldItemList {
  public function grants(EntityInterface $target, $operation) {
    foreach ($this->list as $item) {
      if ($item instanceof EntityOperationItem && $item->matches($target, $operation)) {
        return TRUE;
      }
    }
    return FALSE;
  }
}