<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Provides a 'UpdateTranslations' code.
 */
class UpdateTranslations extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (module_exists('l10n_update')) {
      set_time_limit(0);

      module_load_include('admin.inc', 'l10n_update');
      if (function_exists('l10n_update_language_list')) {
        // TODO: l10n_update version has a completely changed structure which needs a rewrite.
        $steps = array(
          t('Refresh information'),
          t('Update translations'),
        );

        foreach ($steps as $step) {
          l10n_update_get_projects();
          $languages = l10n_update_language_list('name');
          if ($languages) {
            $history = l10n_update_get_history();
            $available = l10n_update_available_releases();
            $updates = l10n_update_build_updates($history, $available);
            $form_state = array(
              'values' => array(
                'op' => $step,
                'mode' => variable_get('l10n_update_import_mode', LOCALE_IMPORT_KEEP),
                // Send the empty array so that all languages get updated.
                'languages' => array(),
                'updates' => $updates,
              ),
            );
            $form = array();
            l10n_update_admin_import_form_submit($form, $form_state);
          }
        }
        $batch = &batch_get();
        if ($batch && !isset($batch['current_set'])) {
          // Set progressive to FALSE if called from xmlrpc.php.
          $batch['progressive'] = FALSE;
          $batch['form_state'] = array(
            'rebuild' => FALSE,
            'programmed' => FALSE,
          );
          batch_process();
        }
      }
    }

    // Allow other modules as well to jump in with translation update routines.
    module_invoke_all('drd_agent_update_translation');
    return array();
  }

}
