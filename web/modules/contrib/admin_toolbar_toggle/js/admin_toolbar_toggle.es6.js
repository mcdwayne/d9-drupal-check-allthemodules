(($, Drupal) => {
  Drupal.behaviors.adminToolbarToggle = {
    attach: (context, settings) => {
      const $toolbar = $(context).find('#toolbar-administration');

      $toolbar.once('admin-toolbar-toggle').each(() => {
        const $body = $('body');
        const $style = $('<style></style>').appendTo($toolbar);

        let state = true;

        const getStyle = () => {
          const rules = `
            margin-top: -${$body.css('padding-top')} !important;
            margin-left: 0 !important;
          `;

          return `body {${rules}}`;
        };

        const toggle = newState => {
          state = typeof newState === 'boolean' ? newState : !state;
          $toolbar.toggle(state);
          $style.html(!state ? getStyle() : '');

          sessionStorage.setItem('hideAdminToolbar', !state);
        };

        if (sessionStorage.getItem('hideAdminToolbar') === 'true') {
          toggle(false);
        }

        $(document).keydown(event => {
          if (event.key === settings.admin_toolbar_toggle.key) {
            toggle();
          }
        });
      });
    },
  };
})(jQuery, Drupal);
