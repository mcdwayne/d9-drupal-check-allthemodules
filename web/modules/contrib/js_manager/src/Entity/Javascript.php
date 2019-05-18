<?php

namespace Drupal\js_manager\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Render\Markup;

/**
 * Defines the Javascript entity.
 *
 * @ConfigEntityType(
 *   id = "javascript",
 *   label = @Translation("Javascript"),
 *   handlers = {
 *     "list_builder" = "Drupal\js_manager\Controller\JavascriptManagerListBuilder",
 *     "storage" = "Drupal\js_manager\Entity\JavascriptStorage",
 *     "form" = {
 *       "add" = "Drupal\js_manager\Form\JavascriptManagerForm",
 *       "edit" = "Drupal\js_manager\Form\JavascriptManagerForm",
 *       "delete" = "Drupal\js_manager\Form\JavascriptManagerDeleteForm"
 *     }
 *   },
 *   config_prefix = "javascript",
 *   admin_permission = "manage javascript items",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/javascript/{javascript}",
 *     "edit-form" = "/admin/structure/javascript/{javascript}/edit",
 *     "delete-form" = "/admin/structure/javascript/{javascript}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "js_type",
 *     "external_js",
 *     "external_js_async",
 *     "inline_js",
 *     "exclude_admin",
 *     "weight",
 *     "scope",
 *   }
 * )
 */
class Javascript extends ConfigEntityBase implements JavascriptInterface {
  use StringTranslationTrait;

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The type.
   *
   * @var string
   */
  protected $js_type = '';

  /**
   * External JS.
   *
   * @var string
   */
  protected $external_js = '';

  /**
   * External JS Async.
   *
   * @var string
   */
  protected $external_js_async = '';

  /**
   * Inline JS.
   *
   * @var string
   */
  protected $inline_js = '';

  /**
   * Exclude on admin paths.
   *
   * @var string
   */
  protected $exclude_admin = FALSE;

  /**
   * Weight.
   *
   * @var string
   */
  protected $weight = 0;

  /**
   * Scope.
   *
   * @var string
   */
  protected $scope = '';

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags = Cache::mergeTags($tags, ['rendered']);
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsType() {
    return (string) $this->js_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalJs() {
    return (string) $this->external_js;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalJsAsync() {
    return (bool) $this->external_js_async;
  }

  /**
   * {@inheritdoc}
   */
  public function getInlineJs() {
    return (string) $this->inline_js;
  }

  /**
   * {@inheritdoc}
   */
  public function excludeAdmin() {
    return (bool) $this->exclude_admin;
  }

  /**
   * {@inheritdoc}
   */
  public function excludeAdminLabel() {
    return $this->exclude_admin ? $this->t('Yes') : $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderArray() {
    switch ($this->getScope()) {
      case 'header':
        $weight = JS_SETTING + $this->getWeight();
        break;

      case 'footer':
        $weight = JS_DEFAULT + $this->getWeight();
        break;
    }
    $custom_script = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'type' => 'text/javascript',
      ],
      '#weight' => $weight,
      '#cache' => [
        'tags' => $this->getCacheTags(),
      ],
    ];
    // If external, add src.
    if ($this->getJsType() == 'external') {
      $custom_script['#attributes']['src'] = $this->getExternalJs();
      // If async enabled, add attribute.
      if ($this->getExternalJsAsync()) {
        $custom_script['#attributes']['async'] = 'async';
      }
    }
    // If internal, add inline js.
    if ($this->getJsType() == 'inline') {
      $custom_script['#value'] = Markup::create($this->getInlineJs());
    }

    return $custom_script;
  }

}
