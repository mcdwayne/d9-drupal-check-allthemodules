<?php

namespace Drupal\applenews\Entity;

use ChapterThree\AppleNewsAPI\Document\Styles\TextStyle;
use Drupal\applenews\ApplenewsTextStyleInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines an Apple News text style configuration entity.
 *
 * @ConfigEntityType(
 *   id = "applenews_text_style",
 *   label = @Translation("Apple News text style"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\applenews\Form\TextStyleForm",
 *       "edit" = "Drupal\applenews\Form\TextStyleForm",
 *       "delete" = "Drupal\applenews\Form\TextStyleDeleteForm",
 *     },
 *     "list_builder" = "Drupal\applenews\TextStyleListBuilder",
 *     "storage" = "Drupal\applenews\TextStyleStorage",
 *   },
 *   config_prefix = "text_style",
 *   admin_permission = "administer applenews text styles",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/applenews/text-style",
 *     "add-form" = "/admin/config/services/applenews/text-style/add",
 *     "edit-form" = "/admin/config/services/applenews/text-style/{applenews_text_style}",
 *     "delete-form" = "/admin/config/services/applenews/text-style/{applenews_text_style}/delete",
 *   }
 * )
 */
class ApplenewsTextStyle extends ConfigEntityBase implements ApplenewsTextStyleInterface {

  /**
   * The name of the text style.
   *
   * @var string
   */
  protected $name;

  /**
   * The image style label.
   *
   * @var string
   */
  protected $label;

  /**
   * Font name.
   *
   * @var string
   */
  protected $fontName;

  /**
   * Font size.
   *
   * @var int
   */
  protected $fontSize;

  /**
   * Text color.
   *
   * @var string
   */
  protected $textColor;

  /**
   * Text shadow.
   *
   * @var string
   */
  protected $textShadow;

  /**
   * Text transform.
   *
   * @var string
   */
  protected $textTransform;

  /**
   * Underline.
   *
   * @var string
   */
  protected $underline;

  /**
   * Strikethrough.
   *
   * @var bool
   */
  protected $strikethrough;

  /**
   * Stroke.
   *
   * @var string
   */
  protected $stroke;

  /**
   * Background color.
   *
   * @var string
   */
  protected $backgroundColor;

  /**
   * Vertical alignment.
   *
   * @var string
   */
  protected $verticalAlignment;

  /**
   * Tracking.
   *
   * @var string
   */
  protected $tracking;

  /**
   * Text alignment.
   *
   * @var string
   */
  protected $textAlignment;

  /**
   * Line height.
   *
   * @var int
   */
  protected $lineHeight;

  /**
   * Drop cap style object.
   *
   * @var object
   *   Drop shadow object.
   */
  protected $dropCapStyle;

  /**
   * String hyphenation.
   *
   * @var string
   */
  protected $hyphenation;

  /**
   * String link style.
   *
   * @var string
   */
  protected $linkStyle;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function toObject() {
    $object = new TextStyle();
    foreach (get_object_vars($this) as $field => $value) {
      $method = 'set' . ucfirst($field);
      if ($value && method_exists($object, $method)) {
        $object->{$method}($value);
      }
    }
    return $object;
  }

}
