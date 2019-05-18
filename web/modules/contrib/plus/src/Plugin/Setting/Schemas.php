<?php

namespace Drupal\plus\Plugin\Setting;

use Drupal\plus\Plus;
use Drupal\plus\Core\Form\SystemThemeSettings;
use Drupal\plus\Utility\Element;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "schemas" theme setting (used to update the theme).
 *
 * @ingroup plugins_setting
 * @ingroup plugins_update
 *
 * @ThemeSetting(
 *   id = "schemas",
 *   type = "hidden",
 *   weight = -20,
 *   groups = false,
 * )
 */
class Schemas extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alter(Element $form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alter($form, $form_state, $form_id);

    $updates = [];
    foreach ($this->theme->getPendingUpdates() as $version => $update) {
      $row = [];
      $row[] = $update->getSchema();
      $row[] = new FormattableMarkup('<strong>@title</strong><p class="help-block">@description</p>', [
        '@title' => $update->getLabel(),
        '@description' => $update->getDescription(),
      ]);
      $row[] = $update->getTheme()->getTitle();
      $updates[] = [
        'class' => [$update->getSeverity() ?: 'default'],
        'data' => $row,
      ];
    }

    $form['update'] = [
      '#type' => 'details',
      '#theme_wrappers' => ['details__theme_updates'],
      '#title' => $this->t('Theme Updates'),
      '#open' => !!$updates,
      '#weight' => -20,
    ];

    // Table.
    $form['update']['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Schema'), $this->t('Description'), $this->t('Provider')],
      '#empty' => $this->t('There are currently no pending updates for this theme.'),
      '#rows' => $updates,
    ];

    // Actions.
    $form['update']['actions'] = [
      '#type' => 'actions',
      '#access' => !!$updates,
    ];
    $form['update']['actions']['update'] = [
      '#type' => 'submit',
      '#theme_wrappers' => ['input__submit__update_theme'],
      '#value' => $this->t('Update theme'),
      '#submit' => [[self::class, 'updateTheme']],
    ];
  }

  /**
   * Callback for updating a theme.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function updateTheme(array $form, FormStateInterface $form_state) {
    if ($theme = SystemThemeSettings::getTheme($form, $form_state)) {
      // Due to the fact that the batch API stores it's arguments in DB storage,
      // theme based objects cannot be passed as an operation argument here.
      // During _batch_page(), the DB item will attempt to restore the arguments
      // using unserialize() and the autoload fix include added below may not
      // yet have been invoked to register the theme namespaces. So instead,
      // we capture the relevant information needed to reconstruct these objects
      // in the batch processing callback.
      $theme_name = $theme->getName();

      // Create an operation for each update.
      $operations = [];
      foreach ($theme->getPendingUpdates() as $update) {
        $operations[] = [[__CLASS__, 'batchProcessUpdate'], [$theme_name, $update->getProvider() . ':' . $update->getSchema()]];
      }

      if ($operations) {
        $variables = ['@theme_title' => $theme->getTitle()];
        batch_set([
          'operations' => $operations,
          'finished' => [__CLASS__, 'batchFinished'],
          'title' => t('Updating @theme_title', $variables),
          'init_message' => \Drupal::translation()->formatPlural(count($operations), 'Initializing 1 theme update for @theme_title...', 'Initializing @count theme updates for @theme_title...', $variables),
          'progress_message' => t('Processing update @current of @total...', $variables),
          'error_message' => t('An error was encountered while attempting to update the @theme_title theme.', $variables),
          'file' => Plus::autoloadFixInclude(),
        ]);
      }
    }
  }

  /**
   * Processes an update in a batch operation.
   *
   * @param string $theme_name
   *  The machine name of the theme this update is being applied to.
   * @param string $update_id
   *   The combined identifier of the update being applied, e.g.
   *   provider:schema.
   * @param array $context
   *   The batch context.
   */
  public static function batchProcessUpdate($theme_name, $update_id, array &$context) {
    // Reconstruct the theme object this update is being applied to.
    $theme = Plus::getTheme($theme_name);

    // Reconstruct the update plugin that is being applied.
    list($provider, $plugin_id) = explode(':', $update_id);
    $provider = Plus::getTheme($provider);

    /** @type \Drupal\plus\Plugin\Update\UpdateInterface $update */
    $update = $provider->getUpdateManager()->createInstance($plugin_id, ['theme' => $provider]);

    // Initialize results with theme name and installed schemas.
    if (!isset($context['results']['theme_name'])) {
      $context['results']['theme_name'] = $theme_name;
    }
    if (!isset($context['results']['schemas'])) {
      $context['results']['schemas'] = $theme->getSetting('schemas', []);
    }

    $schemas = &$context['results']['schemas'];

    $variables = [
      '@theme' => $update->getTheme()->getName(),
      '@schema' => $update->getSchema(),
      '@label' => $update->getLabel(),
    ];

    // Perform the update.
    try {
      // Attempt to perform the update.
      if ($update->update($theme, $context) === FALSE) {
        throw new \Exception(t('Update failed'));
      }

      // Store the results.
      $schemas[$update->getTheme()->getName()] = $update->getSchema();

      $context['results']['success'][] = t('<strong>[@theme][@schema] @label</strong>', $variables);
    }
      // Capture any errors.
    catch (\Exception $e) {
      $variables['@message'] = $e->getMessage();
      $context['results']['errors'][] = t('<strong>[@theme][@schema] @label</strong> - @message', $variables);
    }
  }

  /**
   * Batch 'finished' callback
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value(s) set in $context['results'] in
   *   \Drupal\plus\Plugin\Setting\Update::batchProcess().
   * @param $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function batchFinished($success, $results, $operations) {
    /** @type \Drupal\plus\Theme $theme */
    // Reconstruct the theme object this update is being applied to.
    $theme = Plus::getTheme($results['theme_name']);

    // Save the current state of the installed schemas.
    $theme->setSetting('schemas', $results['schemas']);

    // Show successful updates.
    if (!empty($results['success'])) {
      $list = Element::create([
        '#theme' => 'item_list__theme_update',
        '#items' => $results['success'],
        '#context' => ['type' => 'success'],
      ]);
      drupal_set_message(new FormattableMarkup('@message' . $list->renderPlain(), [
        '@message' => t('Successfully completed the following theme updates:'),
      ]));
    }

    // Show failed errors.
    if (!empty($results['errors'])) {
      $list = Element::create([
        '#theme' => 'item_list__theme_update',
        '#items' => $results['errors'],
        '#context' => ['type' => 'errors'],
      ]);
      drupal_set_message(new FormattableMarkup('@message' . $list->renderPlain(), [
        '@message' => t('The following theme updates could not be completed:'),
      ]), 'error');
    }
  }

}
