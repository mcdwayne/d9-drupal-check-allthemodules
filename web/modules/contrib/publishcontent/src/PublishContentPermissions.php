<?php

namespace Drupal\publishcontent;

use Drupal\Node\Entity\NodeType;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Render\FormattableMarkup;

class PublishContentPermissions {
  use StringTranslationTrait;

  const PUBLISH_ANY_TYPE = "publish any node type @type";

  const PUBLISH_OWN_TYPE = "publish own node type @type";

  const PUBLISH_EDITABLE_TYPE = "publish editable node type @type";

  const UNPUBLISH_ANY_TYPE = "unpublish any node type @type";

  const UNPUBLISH_OWN_TYPE = "unpublish own node type @type";

  const UNPUBLISH_EDITABLE_TYPE = "unpublish editable node type @type";

  /**
   * @param string $pattern.
   * @param array $arguments
   * @return string
   */
  public static function getPermission($pattern, array $arguments = NULL) {
    return (new FormattableMarkup($pattern, $arguments))->__toString();
  }

  public function permissions() {
    $permissions = [];

    $nodeTypes = NodeType::loadMultiple();
    foreach ($nodeTypes as $type => $data) {
      $titleType = ucfirst($type);

      $permissions[self::getPermission(self::PUBLISH_ANY_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: publish any node type"),
      ];

      $permissions[self::getPermission(self::PUBLISH_OWN_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: publish own node type"),
      ];

      $permissions[self::getPermission(self::PUBLISH_EDITABLE_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: publish editable node type"),
      ];

      $permissions[self::getPermission(self::UNPUBLISH_ANY_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: unpublish any node type"),
      ];

      $permissions[self::getPermission(self::UNPUBLISH_OWN_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: unpublish own node type"),
      ];

      $permissions[self::getPermission(self::UNPUBLISH_EDITABLE_TYPE, ['@type' => $type])] = [
        'title' => $this->t("{$titleType}: unpublish editable node type"),
      ];
    }

    return $permissions;
  }

}
