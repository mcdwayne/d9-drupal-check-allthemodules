<?php

namespace Drupal\node_view_language_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class definition.
 *
 * @category NodeViewPermissionsLanguagePermissions
 *
 * @package Access Control
 */
class NodeViewLanguagePermissionsPermissions {

  use StringTranslationTrait;

  /**
   * Permission function.
   *
   * Added the permissions.
   */
  public function permissions() {
    $permissions = [];
    $nodeTypes = NodeType::loadMultiple();
    foreach ($nodeTypes as $nodeType) {
      /** @var \Drupal\node\Entity\NodeType $nodeType */
      foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL) as $lang) {
        $lang_id = $lang->getId();

        $permission = 'view any ' . $nodeType->id() . ' ' . $lang_id . ' content';
        $permissions[$permission] = [
          'title' => $this->t('<em>@type_label</em>: View any content',
            ['@type_label' => $nodeType->label() . ' ' . strtoupper($lang_id)]),
        ];
        $permission = 'view own ' . $nodeType->id() . ' ' . $lang_id . ' content';
        $permissions[$permission] = [
          'title' => $this->t('<em>@type_label</em>: View own content',
            ['@type_label' => $nodeType->label() . ' ' . strtoupper($lang_id)]),
        ];
      }
    }
    return $permissions;
  }

}
