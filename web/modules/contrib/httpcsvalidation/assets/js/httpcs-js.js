function showCreation() {
  'use strict';
  jQuery('#connectionContainer').css('position', 'absolute').fadeOut('fast', function () {
    jQuery('#connectionContainer').css('position', 'initial');
  });
  jQuery('#creationContainer').css('position', 'absolute').fadeIn('fast', function () {
    jQuery('#creationContainer').css('position', 'initial');
  });
}
function showConnexion() {
  'use strict';
  jQuery('#creationContainer').css('position', 'absolute').fadeOut('fast', function () {
    jQuery('#creationContainer').css('position', 'initial');
  });
  jQuery('#connectionContainer').css('position', 'absolute').fadeIn('fast', function () {
    jQuery('#connectionContainer').css('position', 'initial');
  });
}
function closeNotice() { // eslint-disable-line no-unused-vars
  'use strict';
  jQuery('.messages').fadeOut().remove();
}
(function () {
  'use strict';
  jQuery('#coClick').click(function () {
    showConnexion();
    jQuery('.notice-dismiss').click();
  });
  jQuery('#creaClick').click(function () {
    showCreation();
    jQuery('.notice-dismiss').click();
  });
}());
