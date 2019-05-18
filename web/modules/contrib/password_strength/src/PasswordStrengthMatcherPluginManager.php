<?php

namespace Drupal\password_strength;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\TranslationWrapper;


class PasswordStrengthMatcherPluginManager extends \Drupal\Core\Plugin\DefaultPluginManager {
  /**
   * Constructs a new PasswordStrengthMatcherPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PasswordStrengthMatcher', $namespaces, $module_handler, 'Drupal\password_strength\PasswordStrengthMatcherInterface', 'Drupal\password_strength\Annotation\PasswordStrengthMatcher');
    $this->alterInfo('password_policy_password_strength_matcher_info');
    $this->setCacheBackend($cache_backend, 'password_strength_matcher');
  }

  /**
   * Supplement parent findDefinitions values with Matchers defined in Zxcvbn
   * library.
   *
   * Each entry must be defined as 'NamespacedClass' => 'Description'
   *
   * It then massages the data in the structure needed to represent a plugin
   * definition.
   *
   * @return array
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();

    $zxcvbn_matchers = array(
      'ZxcvbnPhp\Matchers\DateMatch' => 'Matching the use of dates in passwords',
      'ZxcvbnPhp\Matchers\DigitMatch' => 'Matching the use of three or more digits in a row in passwords',
      'ZxcvbnPhp\Matchers\L33tMatch' => 'Matching l33t speak words used in passwords',
      'ZxcvbnPhp\Matchers\RepeatMatch' => 'Matching the use of three or more of the same character in passwords',
      'ZxcvbnPhp\Matchers\SequenceMatch' => 'Matching alphanumerical sequences of characters in passwords',
      'ZxcvbnPhp\Matchers\SpatialMatch' => 'Matching keyboard character spatial locality in passwords',
      'ZxcvbnPhp\Matchers\YearMatch' => 'Matching years in passwords',
      'ZxcvbnPhp\Matchers\DictionaryMatch' => 'Matching words used in passwords pulled from a dictionary',
    );

    foreach ($zxcvbn_matchers as $matcher_class => $matcher_description) {
      $class = ltrim(strrchr($matcher_class, '\\'),'\\');
      $name = 'zxcvbn_' . strtolower($class);
      $definitions[$name] = array(
        'id' => $name,
        'title' => new TranslationWrapper($matcher_description),
        'description' => new TranslationWrapper('Zxcvbn Library ' . $class . ' Matcher'),
        'class' => $matcher_class,
        'provider' => 'password_strength',
      );
    }

    return $definitions;
  }
}