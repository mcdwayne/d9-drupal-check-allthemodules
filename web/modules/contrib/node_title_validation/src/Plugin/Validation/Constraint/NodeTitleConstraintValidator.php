<?php

namespace Drupal\node_title_validation\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Validates the NodeTitleValidate constraint.
 */
class NodeTitleConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new NodeTitleConstraintValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, ConfigFactory $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if ($items->isEmpty()) {
      return;
    }
    $value_title = $items[0]->value;
    $title = explode(' ', $value_title);
    $node_type = $items[0]->getEntity()->getType();
    $node_title_validation_config = $this->configFactory->getEditable('node_title_validation.node_title_validation_settings')
      ->get('node_title_validation_config');
    if ($node_title_validation_config) {
      // Add a comma if comma is blacklist.
      $exclude_comma = [];
      if (!empty($node_title_validation_config['comma-' . $node_type])) {
        $exclude_comma[] = ',';
      }
      // Get exclude values for current content type.
      $type_exclude = isset($node_title_validation_config['exclude-' . $node_type]) ? $node_title_validation_config['exclude-' . $node_type] : '';

      if (!empty($type_exclude) || $exclude_comma) {
        // Replace \r\n with comma.
        $type_exclude = str_replace("\r\n", ',', $type_exclude);
        // Store into array.
        $type_exclude = explode(',', $type_exclude);

        $type_exclude = array_merge($type_exclude, $exclude_comma);

        // Find any exclude value found in node title.
        $findings = _node_title_validation_search_excludes_in_title($value_title, $type_exclude);

        if ($findings) {
          $this->context->addViolation("This characters/words are not allowed to enter in the title. - " . implode(', ', $findings));
        }
      }
    }
    $include_comma = [];
    foreach ($node_title_validation_config as $config_key => $config_value) {
      if ($config_value && $config_key == 'comma-' . $node_type) {
        $include_comma[] = ',';
      }
      if ($config_key == 'exclude-' . $node_type || $include_comma) {
        if (!empty($config_value)) {
          $config_values = array_map('trim', explode(',', $config_value));
          $config_values = array_merge($config_values, $include_comma);
          $findings = [];
          foreach ($title as $title_value) {
            if (in_array(trim($title_value), $config_values)) {
              $findings[] = $title_value;
            }
          }
          if ($findings) {
            $this->context->addViolation("These characters/words are not permitted in the title - " . implode(', ', $findings));
          }
        }
      }
      if ($config_key == 'min-' . $node_type) {
        if (strlen($value_title) < $config_value) {
          $this->context->addViolation("Title should have a minimum $config_value character(s)");
        }
      }
      if ($config_key == 'max-' . $node_type) {
        if (strlen($value_title) > $config_value) {
          $this->context->addViolation("Title should not exceed $config_value character(s)");
        }
      }
      if ($config_key == 'min-wc-' . $node_type) {
        if (count(explode(' ', $value_title)) < $config_value) {
          $this->context->addViolation("Title should have a minimum word count of $config_value");
        }
      }
      if ($config_key == 'max-wc-' . $node_type) {
        if (count(explode(' ', $value_title)) > $config_value) {
          $this->context->addViolation("Title should not exceed a word count of $config_value");
        }
      }
      if ($config_key == 'unique-' . $node_type || $config_key == 'unique') {
        if ($config_value == 1) {
          // Get existing node.
          $nodes = $this->entityTypeManager
            ->getStorage('node')
            ->loadByProperties(['title' => $value_title, 'type' => $node_type]);

          // Get existing node id.
          $nid = $items[0]->getParent()->getEntity()->id();

          // Check for nid.
          if ($nid) {
            // Check for node title by nid.
            $node_title = \Drupal::entityTypeManager()->getStorage('node')->load($nid)->getTitle();

            // Check for existing nid.
            if ($node_title != $value_title && !empty($nodes)) {
              $this->context->addViolation("There is already a node with the title - $value_title");
            }
          }
          else {
            // Check while adding a new node.
            if (!empty($nodes)) {
              $this->context->addViolation("There is already a node with the title - $value_title");
            }
          }
        }
      }
    }
  }

}

/**
 * Helper function to find any exclude values in node title.
 */
function _node_title_validation_search_excludes_in_title($input, array $find) {
  $findings = [];
  // Finding characters in the node title.
  foreach ($find as $char) {
    // Check for single character.
    if (strlen(trim($char)) == 1) {
      if (strpos($input, trim($char)) !== FALSE) {
        $characters = $char == ',' ? '<b>,</b>' : trim($char);
        $findings[] = $characters;
      }
    }
  }

  // Finding words in the node title.
  $words = explode(' ', $input);
  if (!empty($find)) {
    $find = array_map('trim', $find);
  }
  foreach ($words as $word) {
    if (strlen(trim($word)) > 1) {
      if (in_array($word, $find)) {
        $findings[] = $word;
      }
    }
  }

  return $findings;
}
