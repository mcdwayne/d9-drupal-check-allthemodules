<?php
/**
 * @file
 * Contains the G2 Feed controller.
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 */

namespace Drupal\g2\Controller;

/**
 * Class Feed contains the RSS feed controller.
 */
class Feed {
  /**
   * The default description for the feed. Translatable.
   *
   * The @site placeholder is replaced by the default link to the site root.
   *
   * @see _g2_wotd_feed()
   */
  const DEFAULT_DESCRIPTION = 'A daily definition from the G2 Glossary at @site';

  /**
   * The default G2 author in feeds.
   */
  const DEFAULT_AUTHOR = '@author';

}
