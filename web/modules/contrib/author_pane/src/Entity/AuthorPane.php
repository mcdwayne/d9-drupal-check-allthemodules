<?php
/**
 * @file
 * Contains \Drupal\author_pane\Entity\AuthorPane.
 */

namespace Drupal\author_pane\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\user\UserInterface;

/**
 * Defines an AuthorPane configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "author_pane",
 *   label = @Translation("Author Pane"),
 *   handlers = {
 *     "list_builder" = "Drupal\author_pane\Controller\AuthorPaneListBuilder",
 *     "form" = {
 *       "add" = "Drupal\author_pane\Form\AuthorPaneAddForm",
 *       "edit" = "Drupal\author_pane\Form\AuthorPaneEditForm",
 *       "delete" = "Drupal\author_pane\Form\AuthorPaneDeleteForm"
 *     }
 *   },
 *   config_prefix = "author_pane",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/author_pane/edit/{author_pane}",
 *     "delete-form" = "/admin/config/people/author_pane/delete/{author_pane}",
 *     "collection" = "/admin/config/people/author_pane",
 *   }
 * )
 */
class AuthorPane extends ConfigEntityBase {

  /**
   * The ID of the AuthorPane.
   *
   * @var string
   */
  protected $id;

  /**
   * The AuthorPane label.
   *
   * @var string
   */
  protected $label;

  /**
   * The AuthorPane description.
   *
   * @var string
   */
  protected $description;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $author;

  /**
   * Manages the pieces of data.
   *
   * @var mixed
   */
  protected $datumPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    $this->datumPluginManager = \Drupal::service('plugin.manager.authorpane.datum');
  }

  /**
   * Returns the author pane description.
   *
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Sets the user account whose information is displayed on the pane.
   *
   * @param \Drupal\user\UserInterface $author
   */
  public function setAuthor(UserInterface $author) {
    $this->author = $author;
  }

  /**
   * Gets the user account whose information is displayed on the pane.
   *
   * @return \Drupal\user\Entity\User
   */
  public function getAuthor() {
    return $this->author;
  }

  /**
   * Returns a string containing the the author pane.
   *
   * @return string
   */
  public function display() {
    $author = $this->getAuthor();

    if ($author instanceof UserInterface) {
      $author_pane_data = $this->datumPluginManager->getDefinitions();
      $content = '';
      foreach ($author_pane_data as $datum_array) {
        // Make an instance of the datum.
        $datum = $this->datumPluginManager->createInstance($datum_array['id']);
        $datum->setAuthor($author);
        // @TODO: Theme this properly with a template.
        $content .= $datum->output();
      }
    }
    else {
      $content = $this.t('No author set.');
    }

    return $content;
  }

}
