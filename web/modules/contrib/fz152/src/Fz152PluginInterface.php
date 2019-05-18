<?php

namespace Drupal\fz152;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Provides an interface for fz152 settings plugins.
 *
 * @todo Use \Drupal\Core\Plugin\PluginWithFormsInterface
 */
interface Fz152PluginInterface extends PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getId();

  /**
   * If you want to add settings as tab to main settings you can define it here.
   *
   * Otherwise define the page by yourself.
   *
   * @return array
   *   Possible values:
   *   - "path": The tab path additional: /admin/config/fz152/[PATH].
   *   - "title": String with title for tab and page with settings.
   *   - "form": As in routing.yml file must contains form Class.
   *   - "weight": Weight for tab on page.
   */
  public function getSettingsPage();

  /**
   * Returns a list of form to and confirmation for sending private data.
   *
   * @return array
   *   Array with form names to add checkbox:
   *   - "form_id": The form id. Can contains wildcards "*".
   *   - "weight": (optional) The weight for checkbox in the form.
   */
  public function getForms();

}
