// $Id$

/**
 * @file
 * JavaScript for the Views Ooyala shared player output.
 */
setTimeout('', 1); //does the timeout work?
jQuery.each(Drupal.settings.ooyalaSharedPlayerCodes, function(i, val) {
  $('#' + Drupal.settings.ooyalaSharedPlayer + "-" + i).click(function() {
    document.getElementById(Drupal.settings.ooyalaSharedPlayer).setQueryStringParameters({embedCode: val});
  });
});

function receiveOoyalaEvent(playerId, eventName, p) {
  switch(eventName) {
    case 'embedCodeChanged':
     console.log(p);
      $('#title-' + playerId ).empty().append(p.title);
      document.getElementById(playerId).playMovie();
    break;
    case 'loadComplete':
      var description = document.getElementById(Drupal.settings.ooyalaSharedPlayer).getCurrentItem();
      $('#title-' + playerId ).empty().append(description.title);
    break;
  }
  return;
}
