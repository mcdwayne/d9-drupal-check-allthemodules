<?php

/**
 * @file
 * Contains \Drupal\collect\Controller\FieldDefinitionDiffController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\Entity\Container;
use Drupal\collect\Plugin\collect\Model\CollectJson;
use Drupal\collect\Plugin\collect\Model\FieldDefinition;
use Drupal\collect\Model\ModelInterface;
use Drupal\Component\Diff\Diff;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\collect\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Returns responses for FieldDefinitionDiffController routes.
 */
class FieldDefinitionDiffController extends ControllerBase {

  /**
   * DiffFormatter service.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * SerializerInterface which is used for serialization.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer
   */
  protected $serializer;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new FieldDefinitionDiffController.
   */
  public function __construct(DiffFormatter $diff_formater, NormalizerInterface $serializer, EntityManagerInterface $entity_manager) {
    $this->diffFormatter = $diff_formater;
    $this->serializer = $serializer;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('diff.formatter'),
      $container->get('serializer.normalizer.field_definition'),
      $container->get('entity.manager')
    );
  }

  /**
   * Compares two field definitions and builds the table.
   */
  public function compareFieldDefinitions(ModelInterface $collect_model) {
    $build = ['#title' => $this->t('Compare with current Field Definition data')];

    $field_definition_container_id = FieldDefinition::findDefinitionContainer($collect_model);
    $stored_field_definition = Json::decode(Container::load($field_definition_container_id)->getData())['fields'];
    $stored_field_definition_json = Json::encodePretty(FieldDefinition::removeEntityReferenceFields($stored_field_definition));
    $current_field_definition_json = $this->getCurrentFieldDefinitions($collect_model);

    $field_diff_rows = $this->getRows($stored_field_definition_json, $current_field_definition_json);

    // Add the CSS for the diff.
    $build['#attached']['library'][] = 'collect/collect.default';

    $build['diff'] = array(
      '#type' => 'table',
      '#header' => [NULL, $this->t('Stored Field Definition'), NULL, $this->t('Current Field Definition')],
      '#rows' => $field_diff_rows,
      '#empty' => $this->t('Compared Field Definitions are the same.'),
      '#attributes' => array(
        'class' => array('diff'),
      ),
    );

    return $build;
  }

  /**
   * Checks whether user has a permission to compare Field Definitions.
   */
  public function checkCompareFieldDefinitionsAccess(ModelInterface $collect_model) {
    if ($field_definition_container_id = FieldDefinition::findDefinitionContainer($collect_model)) {
      return AccessResult::allowedIf((bool) Container::load($field_definition_container_id));
    }
    return AccessResult::forbidden();
  }

  /**
   * Returns current Field Definition data.
   */
  public function getCurrentFieldDefinitions(ModelInterface $collect_model) {
    $matches = CollectJson::matchSchemaUri($collect_model->getUriPattern());
    $bundle = $matches['bundle'];
    if (!$bundle) {
      $bundle = $matches['entity_type'];
    }
    $field_definitions = $this->entityManager->getFieldDefinitions($matches['entity_type'], $bundle);
    $fields = [];
    foreach ($field_definitions as $field_name => $definition) {
      $fields[$field_name] = $this->serializer->normalize($field_definitions[$field_name], 'json');
    }
    return Json::encodePretty($fields);
  }

  /**
   * Returns generated rows for the table.
   *
   * @return array
   */
  protected function getRows($stored_field_definition, $current_field_definition) {
    $stored_field_definition = explode("\n", $stored_field_definition);
    $current_field_definition = explode("\n", $current_field_definition);

    // Header is the line counter.
    $this->diffFormatter->show_header = TRUE;

    $this->diffFormatter->leading_context_lines = 3;
    $this->diffFormatter->trailing_context_lines = 3;
    $diff = new Diff($stored_field_definition, $current_field_definition);

    return $this->diffFormatter->format($diff);
  }

}
