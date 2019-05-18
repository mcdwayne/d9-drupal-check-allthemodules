<?php

namespace Drupal\ext_redirect\Service;

use Drupal\ext_redirect\Entity\RedirectRule;
use Drupal\ext_redirect\Service\RedirectRuleRepository;

/**
 * Class RedirectRuleMatcher.
 */
class RedirectRuleMatcher implements RedirectRuleMatcherInterface {

  /**
   * Drupal\ext_redirect\Service\RedirectRuleRepository definition.
   *
   * @var \Drupal\ext_redirect\Service\RedirectRuleRepository
   */
  protected $redirectRepository;

  /**
   * Constructs a new RedirectRuleMatcher object.
   */
  public function __construct(RedirectRuleRepository $ext_redirect_repository) {
    $this->redirectRepository = $ext_redirect_repository;
  }

  /**
   * Lookup for a redirect rule.
   *
   * @param $host string
   *    Host extracted from request URI
   * @param $path string
   *    URI path (maybe also path alias)
   *
   * @return RedirectRule|null
   *    Return RedirectRule entity if any rule is matched.
   */
  public function lookup($host, $path = '') {
    $rule = NULL;

    if (empty($path)) {
      $rule = $this->redirectRepository->getRuleForHostWithoutPath($host);
    }
    else {
      $host_rules = $this->redirectRepository->getHostRules($host);
      if ($host_rules) {
        $rule = $this->findMatchRule($host_rules, $path);
      }
    }

    if (!$rule && !empty($path)) {
      $global_rules = $this->redirectRepository->getGlobalRules();
      if ($global_rules) {
        $rule = $this->findMatchRule($global_rules, $path);
      }
    }

    return $rule;
  }

  /**
   * @param array $rules
   *    Rules data source
   * @param $path string
   *    Path to look for
   *
   * @return RedirectRule|null
   *    Found rule or null instead.
   */
  private function findMatchRule(array $rules, $path) {
    /** @var RedirectRule $rule */
    foreach ($rules as $rule) {
      if ($rule->getSourcePath() == '*') {
        return $rule;
      }
      else {
        $paths = preg_split('/\n|\r\n?/', $rule->getSourcePath());
        if (in_array($path, $paths)) {
          return $rule;
        }
      }
    }
    return NULL;
  }
}
