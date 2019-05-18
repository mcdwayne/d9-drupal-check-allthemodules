(function ($, Drupal) {
  var tabId = sessionStorage.tabId && sessionStorage.closedLastTab !== '2' ? sessionStorage.tabId : sessionStorage.tabId = Math.round(10000000 * Math.random()) + Date.now().toString();
  var nodeId = window.drupalSettings.path.nid;

  $(document).ready(function (e) {
    sessionStorage.closedLastTab = '2';
    Drupal.restconsumer.patch('/pagedesigner/lock/' + nodeId, { identifier: tabId }).done(function ($result) {
      if ($result[0] === true) {
        $(document).on('submit', '#page-settings form', function (e) {
          e.preventDefault();

          const openSettings = editor.Panels.getButton('sidebar', 'sidebar-open-settings');
          openSettings && openSettings.set('active', false);

          Drupal.restconsumer.submit($(this), {}, '', {
            success: function(){ return true; },
            complete: function(){ return true; }
          }).done(function () {
            document.getElementById('page-settings').innerHTML = '';
            const settings_form = document.createElement('div');
            settings_form.id = 'page-settings-form';
            document.getElementById('page-settings').appendChild(settings_form);
            Drupal.ajax({ url: '/node/' + nodeId + '/edit', wrapper: 'page-settings-form' }).execute();
          });
        });

        Drupal.restconsumer.get('/pagedesigner/pattern').done(function (patterns) {
          window.grapes_plugins = [];
          window.grapes_plugins.push('grapesjs-parser-postcss');
          initPDGrapes(patterns);
        });
      } else {
        document.location = window.location.href.split('?')[0] + '?otherTab=1';
      }
    });
  });

  $(window).on('beforeunload', function () {
    sessionStorage.closedLastTab = '1';
    Drupal.restconsumer.delete('/pagedesigner/lock/' + nodeId, { identifier: tabId });
  });

})(jQuery, Drupal);
