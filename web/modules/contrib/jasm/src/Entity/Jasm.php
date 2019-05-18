<?php
namespace Drupal\jasm\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\jasm\JasmInterface;

/**
 * Defines the Jasm entity.
 *
 * @ConfigEntityType(
 *   id = "jasm",
 *   label = @Translation("JASM service"),
 *   handlers = {
 *     "list_builder" = "Drupal\jasm\Controller\JasmListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jasm\Form\JasmForm",
 *       "edit" = "Drupal\jasm\Form\JasmForm",
 *       "delete" = "Drupal\jasm\Form\JasmDeleteForm",
 *     }
 *   },
 *   config_prefix = "jasm",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/jasm/{jasm}",
 *     "delete-form" = "/admin/config/system/jasm/{jasm}/delete",
 *   }
 * )
 */
class Jasm extends ConfigEntityBase implements JasmInterface {

  /**
   * The Jasm ID.
   *
   * @var string
   */
  public $id;
  
  /**
   * The Jasm label. This is the text that will display as the link text
   *
   * @var string
   */
  public $label;
  
  /**
   *  Status. Wheter or not this JASM service should be displayed in lists, etc.
   *
   * @var boolean
   */
  public $status;
  
  /**
   * The (probably external) URL of the 3rd party social service. The link to
   * to page essentially
   *
   * @var string
   */
  public $service_page_url;
  
  /**
   * The flower color (don't ask)
   *
   * @var string
   */
  public $color;
  
  /**
   * The number of petals
   *
   * @var int
   */
  public $petals;
  
  /**
   * The season in which this flower can be found
   *
   * @var string
   */
  public $season;
}