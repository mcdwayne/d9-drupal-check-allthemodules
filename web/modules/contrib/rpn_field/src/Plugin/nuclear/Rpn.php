<?php

namespace Drupal\rpn_field\Plugin\nuclear;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Utility\Token;
use Drupal\nuclear\NuclearPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PluginID("rpn")
 */
class Rpn extends PluginBase implements NuclearPluginInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Queue constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /*
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /*
   * {@inheritdoc}
   */
  public function react(FieldableEntityInterface $entity, $field_name, $all_entities, $path) {
    $rpn_string = $this->configuration['rpn'];
    $save = FALSE;
    $field_item_list = $entity->get($field_name);
    if (!$field_item_list->isEmpty()) {
      $value = $field_item_list->get(0)->value;
      if (preg_match_all('/@(.*?):/', $rpn_string, $matches)) {
        $arguments = [];
        foreach ($matches[1] as $index) {
          $argument = $all_entities[$index];
          $entity_type_id = $argument->getEntityTypeId();
          $arguments[$entity_type_id] = $argument;
          $rpn_string = str_replace("@$index", $entity_type_id, $rpn_string);
        }
        $rpn_string = $this->token->replace($rpn_string, $arguments);
      }
      $new_value = $this->evaluate($value, $rpn_string);
      if ($new_value !== $value) {
        $field_item_list->get(0)->value = $new_value;
        $save = TRUE;
      }
    }
    if ($save) {
      $entity->save();
    }
  }

  /**
   * Evaluates an rpn string.
   *
   * @param $value
   *   The starting value on the stack.
   * @param $rpn_string
   *   The RPN string
   *
   * @return integer
   *   The end result.
   */
  protected function evaluate($value, $rpn_string) {
    $elements = preg_split('#\s+#', $rpn_string, NULL, PREG_SPLIT_NO_EMPTY);
    $stack = new \SplStack();
    $push = function ($value) use ($stack) {
      $stack->push($value);
    };
    $pop = function() use ($stack) {
      return $stack->pop();
    };
    array_unshift($elements, $value);
    foreach ($elements as $element) {
      if ($element === '!dup') {
        $arg = $pop();
        $push($arg);
        $push($arg);
      }
      else {
        switch ($element) {
          case '+':
            $push($pop() + $pop());
            break;
          case '-':
            $push($pop() - $pop());
            break;
          case '*':
            $push($pop() * $pop());
            break;
          case '/':
            $push($pop() / $pop());
            break;
          case '==':
            $push(intval($pop() == $pop()));
            break;
          case '<>':
            $push(intval($pop() <> $pop()));
            break;
          case '!swap':
            $arg1 = $pop();
            $arg2 = $pop();
            $push($arg1);
            $push($arg2);
            break;
          default:
            $push($element);
            break;
        }
      }
    }
    return $pop();
  }

}
