<?php

namespace Drupal\autocomplete_node_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form field for searching node items.
 */
class AutocompleteNodeSearch extends FormBase {

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */

  protected $entityQuery;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new AutocompleteQueryHandler.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity query factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity Manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The Alias Manager interface.
   */
  public function __construct(QueryFactory $entityQuery, EntityManagerInterface $entityManager, AliasManagerInterface $alias_manager) {
    $this->entity_query = $entityQuery;
    $this->entityManager = $entityManager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autocomplete_node_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['node_items'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'autocomplete_node_search.autocomplete',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search = trim($form_state->getValue('node_items'));
    $query = $this->entity_query->get('node')
      ->condition('status', 1)
      ->condition('title', trim($search))
      ->addTag('node_access');
    $nids = $query->execute();

    if (!empty($nids)) {
      $node_storage = $this->entityManager->getStorage('node');
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $value) {
        $titles[$value->get('nid')->value] = trim($value->get('title')->value);
      }
      $returnKey = array_search($search, $titles);
      $form_state->setRedirect('entity.node.canonical', ['node' => $returnKey]);
    }
    else {
      $form_state->setRedirect('<front>'); return;
    }
  }

}
