/**
 * @file
 * Logs ARIA-live announcements made via Drupal.announce.
 */

(function (Drupal, console) {

var proxied = Drupal.announce;
Drupal.announce = function (text, priority) {
  var eventualPriority = (priority === 'assertive') ? 'assertive' : 'polite';
  console.info('%s Drupal announcement: "%s"', eventualPriority, text);
  return proxied.apply(this, arguments);
};

}(Drupal, window.console));
