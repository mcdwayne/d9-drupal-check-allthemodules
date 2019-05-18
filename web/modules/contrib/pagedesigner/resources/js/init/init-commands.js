(function ($, Drupal) {
  Drupal.behaviors.pagedesigner_init_base_commands = {
    attach: function (context, settings) {
      $(document).on('pagedesigner-init-base-commands', function (e, editor) {

        editor.Commands.add('set-device-desktop', editor => {
          editor.setDevice('Desktop');
          jQuery('[data-responsive-options]').find('label').removeClass('active');
          jQuery('[data-responsive-options]').find('label[data-device="' + editor.getDevice() + '"]').addClass('active')
        });
        editor.Commands.add('set-device-tablet', editor => {
          editor.setDevice('Tablet');
          jQuery('[data-responsive-options]').find('label').removeClass('active');
          jQuery('[data-responsive-options]').find('label[data-device="' + editor.getDevice() + '"]').addClass('active')
        });
        editor.Commands.add('set-device-mobile', editor => {
          editor.setDevice('Mobile portrait');
          jQuery('[data-responsive-options]').find('label').removeClass('active');
          jQuery('[data-responsive-options]').find('label[data-device="' + editor.getDevice() + '"]').addClass('active')
        });

        // save component
        editor.Commands.add('save-component', (editor, sender) => {
          sender && sender.set('active', false);
          if (editor.getSelected()) {
            editor.getSelected().save();

            const openTraits = editor.Panels.getButton('sidebar', 'sidebar-open-traits');
            openTraits && openTraits.set('active', false);

            const openStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
            openStyles && openStyles.set('active', false);

            editor.select();
            editor.stopCommand('edit-component');
          }
        });

        // restore component
        editor.Commands.add('restore-component', (editor, sender) => {
          if( sender != editor ){
            sender && sender.set('active', false);
          }



          if (editor.getSelected() && ( editor.getSelected().get('changed') || JSON.stringify(editor.getSelected().serialize().styles) !=  JSON.stringify(editor.getSelected().get('previousVersion').styles) ) ) {
            if (confirm(Drupal.t("Do you want to cancel your changes?"))) {

              editor.getSelected().restore();
              editor.select();
              editor.stopCommand('edit-component');
            }
          } else {
            editor.select();
            editor.stopCommand('edit-component');
          }
        });

        // delete component
        editor.Commands.add('tlb-delete-component', {
          run(editor) {
            if (editor.getSelected() && confirm('Do you want to delete the component?')) {
              editor.getSelected().delete();
              editor.runCommand('core:component-delete');
              editor.select();
              const openTraits = editor.Panels.getButton('sidebar', 'sidebar-open-traits');
              openTraits && openTraits.set('active', false);
              const openStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
              openStyles && openStyles.set('active', false);
            }
          }
        });

        // top menu
        editor.Commands.add('sw-visibility', {
          run(ed) {
            ed.Canvas.getBody().classList.add(this.ppfx + 'dashed');
          },
          stop(ed) {
            ed.Canvas.getBody().classList.remove(this.ppfx + 'dashed');
          }
        });

        editor.Commands.add('tlb-copy-component', {
          run(ed) {
            if (editor.getSelected() && confirm('Do you want to copy the element?')) {
              editor.getSelected().clone();
            }
          }
        });

        // help
        editor.Commands.add('open-help', {
          run(editor) {
            const lm = editor.LayerManager;
            const pn = editor.Panels;
            if (!this.help) {
              const id = 'views-container';
              const help = document.createElement('div');
              const panels = pn.getPanel(id) || pn.addPanel({ id });

              help.appendChild(jQuery('<div><p class="sidebar-title">' + Drupal.t('Help') + '</p><div style="text-align: left"><iframe width="100%" height="315" src="https://www.youtube.com/embed/2Q_ZzBGPdqE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div></div>').get(0));
              panels.set('appendContent', help).trigger('change:appendContent');
              this.help = help;
            }
            this.help.style.display = 'block';

            editor.Panels.getPanel('views-container').set('visible', true);
          },
          stop() {
            const help = this.help;
            help && (help.style.display = 'none');
            editor.Panels.getPanel('views-container').set('visible', false);
          }
        });



        // settings
        editor.Commands.add('open-settings', {
          run(editor) {
            // TODO: Fix pagesettings in panel
            // Redirect to backend in the meantime
            Object.assign(document.createElement('a'), { target: '_blank', href: '/node/' + window.drupalSettings.path.nid + '/edit' }).click()
            return;
            editor.Panels.getPanel('views-container').set('visible', true);
            const pn = editor.Panels;
            if (!this.page_settings) {
              const id = 'views-container';
              const page_settings = document.createElement('div');
              page_settings.id = 'page-settings';

              var title_settings = document.createElement('p');
              title_settings.classList.add("sidebar-title");
              title_settings.innerHTML = Drupal.t('Page settings');
              page_settings.appendChild(title_settings);

              const settings_form = document.createElement('div');
              settings_form.id = 'page-settings-form';
              page_settings.appendChild(settings_form);
              const panels = pn.getPanel(id) || pn.addPanel({ id });
              panels.set('appendContent', page_settings).trigger('change:appendContent');
              this.page_settings = page_settings;
              Drupal.ajax({ url: '/node/' + window.drupalSettings.path.nid + '/edit', wrapper: 'page-settings-form' }).execute();
            }
            this.page_settings.style.display = 'block';

          },
          stop() {
            const page_settings = this.page_settings;
            page_settings && (page_settings.style.display = 'none');
            editor.Panels.getPanel('views-container').set('visible', false);
          }
        });

        // edit component
        editor.Commands.add('edit-component', {
          run(editor) {
            if (editor.getSelected()) {
              if (editor.getSelected().get('traits').length > 0) {
                const openTraits = editor.Panels.getButton('sidebar', 'sidebar-open-traits');
                openTraits && openTraits.set('active', true);
              } else {
                const openStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
                openStyles && openStyles.set('active', true);
              }
            }
          },
          stop() {
            editor.select();
            const openTraits = editor.Panels.getButton('sidebar', 'sidebar-open-traits');
            openTraits && openTraits.set('active', false);

            const openStyles = editor.Panels.getButton('sidebar', 'sidebar-open-styles');
            openStyles && openStyles.set('active', false);
          }
        });

      });
    }
  };
})(jQuery, Drupal);
