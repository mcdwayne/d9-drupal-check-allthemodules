<?php

namespace Drupal\hidden_tab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PlacementAddController komponent placement form controller for layout.
 *
 * Workflow: page layout form -> place komponent -> this form opens -> it
 * expects a page and a komponent type in the query (by the layout form), so it
 * knows what to load (komponents of specific type, and for a specific page).
 *
 * <b>THIS MODULE</b> is adopted from core's <em>block</em> module.
 *
 * @see \Drupal\block\Controller\BlockAddController
 * @see \Drupal\hidden_tab\Controller\KomponentLibraryController
 */
class PlacementAddController extends ControllerBase {

  /**
   * Hidden Tab Placement storage, to create a new placement.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $placementStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $placementStorage) {
    $this->placementStorage = $placementStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('hidden_tab_placement')
    );
  }

  /**
   * Creates placement add form.
   *
   * @param string $target_hidden_tab_page
   *   The page to which komponent is being added to.
   * @param string $region
   *   The region of the template (configured in page) to which this komponent
   *   is being put into.
   * @param string $komponent_type
   *   Type of komponent (the plugin) (such as views).
   * @param string $weight
   *   Weight among other komponents of the same region.
   * @param string|null $lredirect
   *   Where to redirect to afterwards.
   *
   * @return array
   *   Entity add form.
   */
  public function placementAddConfigureForm(string $target_hidden_tab_page,
                                            string $region,
                                            string $komponent_type,
                                            string $weight,
                                            string $lredirect = NULL): array {
    // Page is provided by the KomponentLibraryController page.
    $entity = $this->placementStorage
      ->create([
        'target_hidden_tab_page' => $target_hidden_tab_page,
        'region' => $region,
        'komponent_type' => $komponent_type,
        'weight' => $weight,
        'permission' => 'access content',
        'id' => $this->findNextValidId($target_hidden_tab_page, $komponent_type),
      ]);

    return $this->entityFormBuilder()->getForm($entity, 'default', [
      'lredirect' => $lredirect,
    ]);
  }

  /**
   * Creates a valid id, by appending incrementally a number.
   *
   * In case an ID already exists. IDs are generated from context(komponent
   * type, komponent config and ...) and usually not entered by user. So we
   * try to generate a new one if the ID already exists.
   *
   * @param string $page_id
   *   The page to which the placement is added.
   * @param string $komponent_type
   *   Type of the komponent (the plugin).
   *
   * @return string
   *   A valid ID.
   */
  private function findNextValidId(string $page_id, string $komponent_type): string {
    // Base id.
    $_id = $page_id . '__' . str_replace('::', '___', $komponent_type);
    $id = $_id;
    $i = 0;
    // Increase until a valid (un-used one) is found.
    while ($this->placementStorage->load($id)) {
      $i++;
      $id = $_id . "_$i";
    }
    return $id;
  }

}
