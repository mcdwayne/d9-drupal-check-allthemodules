/**
 * @file
 * Pardot JavaScript and parameters.
 */

var piAId = drupalSettings.pardot.accountId;
var piCId = drupalSettings.pardot.campaignId;
var piPoints = drupalSettings.pardot.score || '';

(function() {
  "use strict";

  function async_load(){
    var s = document.createElement('script'); s.type = 'text/javascript';
    s.src = ('https:' == document.location.protocol ? 'https://pi' : 'http://cdn') + '.pardot.com/pd.js';
    var c = document.getElementsByTagName('script')[0]; c.parentNode.insertBefore(s, c);
  }
  if(window.attachEvent) { window.attachEvent('onload', async_load); }
  else { window.addEventListener('load', async_load, false); }
})();
