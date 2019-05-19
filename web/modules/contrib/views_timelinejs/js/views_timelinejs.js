/**
 * @file
 * Invokes the TimelineJS library for each timeline listed in drupalSettings.
 */

(function () {
  drupalSettings.TimelineJS.forEach(function(timeline, key) {
    if (timeline['processed'] != true) {
      window.timeline = new TL.Timeline(timeline['embed_id'], timeline['source'], timeline['options']);
    }
    timeline['processed'] = true;
  });
})();
