(function (Drupal) {
  Drupal.mm_back_in_history = function (save) {
    if (save) {
      document.cookie = 'mm_last_page=' + encodeURI(document.location) + ';path=/';
    }
    else {
      var matches = document.cookie.match(/\bmm_last_page=(.*?)(;|$)/);
      if (matches && matches.length && matches[1].length) {
        var date = new Date(0);
        document.cookie = "mm_last_page=;expires=" + date.toUTCString() + ";path=/";
        document.cookie = 'goto_last=1;path=/';
        document.location = matches[1];
      }
      else {
        document.cookie = 'goto_last=1;path=/';
        window.history.back(-1);
      }
      return false;
    }
  };
})(Drupal);
