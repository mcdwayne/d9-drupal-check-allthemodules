<?php

namespace Drupal\entity_pager;

/**
 * An interface for an Entity Pager Analyzer.
 */
interface EntityPagerAnalyzerInterface {

  /**
   * @param \Drupal\entity_pager\EntityPagerInterface $entityPager
   * @return null
   */
  function analyze(EntityPagerInterface $entityPager);
}
