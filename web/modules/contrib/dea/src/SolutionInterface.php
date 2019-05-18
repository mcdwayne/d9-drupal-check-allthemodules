<?php

namespace Drupal\dea;


interface SolutionInterface {

  /**
   * {@inheritdoc}
   */
  public function __toString();

  /**
   * Check if this solution is already applied.
   */
  public function isApplied();

  /**
   * {@inheritdoc}
   */
  public function applyDescription();
  
  /**
   * Apply the solution.
   */
  public function apply();
  
  /**
   * Revoke the solution.
   */
  public function revoke();
  
  /**
   * {@inheritdoc}
   */
  public function revokeDescription();
}