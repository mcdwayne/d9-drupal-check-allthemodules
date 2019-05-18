/**
 * @file
 * Drupal+ Loader.
 */
(($, Drupal) => {
  'use strict';

  // Immediately return if Drupal loader is already on the page.
  if (Drupal.loader) {
    return;
  }

  // Indicate that this JS is already on the page.
  Drupal.loader = true;

  // Immediately return if there are no files.
  if (!Drupal.settings.loaderFiles || !Drupal.settings.loaderFiles.length) {
    Drupal.warning(Drupal.t('There were no files to load. This is usually an indication that the Drupal rendering process has been corrupted.'));
    return;
  }

  // Don't load these asynchronously because the order in which the files are
  // attached is important since they were already sorted by Drupal to account
  // for libraries and dependencies.
  const files = Drupal.settings.loaderFiles.map(file => Drupal.addToDom(file, { async: false }));
  debugger;
  Promise.all(files)
    .catch(error => error instanceof Error && Drupal.fatal(error))
    .done(() => {
      /**
       * An asynchronous queue for processing behaviors.
       *
       * @type {AsyncQueue}
       */
      Drupal.behaviorsQueue = Drupal.AsyncQueue.create(Drupal.behaviors, function behaviorsQueueCallback(success) {
        if (success === false) {
          return new Error(Drupal.t('Drupal behavior "@id" failed to @type @context.', {
            '@id': this.__asyncQueueId__,
            '@type': this.__asyncQueueMethod__ === 'detach' ? Drupal.t('detach from') : Drupal.t('attach onto'),
            '@context': Drupal.sanitizeObject(this.__asyncQueueArguments__[0]),
          }));
        }
        return success;
      });

      // Resume Drupal behaviors.
      Drupal.holdBehaviors(false);

      // Resume jQuery ready.
      if ($ && typeof $.holdReady === 'function') {
        $.holdReady(false);
      }
    });
})(window.jQuery, window.Drupal);
