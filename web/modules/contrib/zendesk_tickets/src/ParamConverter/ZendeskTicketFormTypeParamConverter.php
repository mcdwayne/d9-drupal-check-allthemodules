<?php

namespace Drupal\zendesk_tickets\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting Ticket form types to full objects.
 *
 * This converters an id or a machine name as the parameter.
 */
class ZendeskTicketFormTypeParamConverter implements ParamConverterInterface {

  /**
   * Entity manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    if ($storage = $this->entityManager->getStorage($entity_type_id)) {
      $entity = NULL;
      if (is_numeric($value)) {
        $entity = $storage->load($value);
      }
      elseif (is_string($value)) {
        $value_string = (string) $value;
        $entity_class = $this->entityManager->getDefinition('zendesk_ticket_form_type')->getClass();
        $value_string = $entity_class::convertMachineNameFromUrlPath($value_string);
        $entities = $storage->loadByProperties(['machineName' => $value_string]);
        if ($entities) {
          $entity = reset($entities);
        }
      }

      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      if (isset($entity) && $entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $entity = $this->entityManager->getTranslationFromContext($entity, NULL, array('operation' => 'entity_upcast'));
      }
      return $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'zendesk_tickets:') === 0) {
      $entity_type_id = substr($definition['type'], strlen('zendesk_tickets:'));
      if (strpos($definition['type'], '{') !== FALSE) {
        $entity_type_slug = substr($entity_type_id, 1, -1);
        return $name != $entity_type_slug && in_array($entity_type_slug, $route->compile()->getVariables(), TRUE);
      }
      return $this->entityManager->hasDefinition($entity_type_id);
    }
    return FALSE;
  }

  /**
   * Determines the entity type ID given a route definition and route defaults.
   *
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   *
   * @return string
   *   The entity type ID.
   *
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   *   Thrown when the dynamic entity type is not found in the route defaults.
   */
  protected function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    $entity_type_id = substr($definition['type'], strlen('zendesk_tickets:'));

    // If the entity type is dynamic, it will be pulled from the route defaults.
    if (strpos($entity_type_id, '{') === 0) {
      $entity_type_slug = substr($entity_type_id, 1, -1);
      if (!isset($defaults[$entity_type_slug])) {
        throw new ParamNotConvertedException(sprintf('The "%s" parameter was not converted because the "%s" parameter is missing', $name, $entity_type_slug));
      }
      $entity_type_id = $defaults[$entity_type_slug];
    }
    return $entity_type_id;
  }

}
