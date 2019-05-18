(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.MMSettingsPerms = {
    attach: function (context) {
      $('.mm-permissions', context).once('mm-permissions').each(function () {
        $(':checkbox:not(#edit-node-everyone)', this)
          .change(function (event, recurs) {
            var id = this.name.match(/(?:^|\[)(\w+)-(\w)-(\w+)/);    // user-mode-uid or group-mode-gid
            var row = $(this).closest('tr');
            var tbody = $(this).closest('tbody');
            var list;

            $('form#' + drupalSettings.MM.settings_perms.form_id + ' .messages').remove();
            if (id[3] == 'everyone') {
              // Everything else in column
              list = $(':checkbox:not(.mm-permissions-disabled)[name*=-' + id[2] + ']:not([name="' + this.name + '"])', tbody);
              if (this.checked) {
                list.prop('checked', true);
              }
              list
                .toggleClass('mm-permissions-col-disabled', this.checked)
                .attr('disabled', this.checked);
              if (!recurs) {
                $(':checkbox:checked:not([name="' + this.name + '"])', tbody)
                  .trigger('change', true);
              }
            }

            if (id[2] != drupalSettings.MM.settings_perms.r) {
              if (id[2] == drupalSettings.MM.settings_perms.w) {
                // The rest of the row
                list = $(':checkbox:not([name="' + this.name + '"],.mm-permissions-disabled,.mm-permissions-col-disabled)', row);
              }
              else {
                // r
                list = $(':checkbox[name*=-' + drupalSettings.MM.settings_perms.r + '-]:not(.mm-permissions-disabled,.mm-permissions-col-disabled)', row);
              }

              list.attr('disabled', this.checked);
              if (this.checked) {
                list.prop('checked', true);
              }
              else if (!recurs) {
                $(':checkbox:checked[name^=' + id[1] + '-]:not([name="' + this.name + '"],[name*=-' + drupalSettings.MM.settings_perms.r + '-])', row)
                  .trigger('change', true);
              }
            }
          });

        // node perms "everyone"
        $('#edit-node-everyone', this)
          .change(function () {
            if ($(this).is(':checked')) {
            $(this)
              .closest('tbody')
              .children('tr.mm-permissions-data-row')
              .fadeOut(500, function () {
                $(this).remove();
              });
            }
          });

        // initial setup
        $('.mm-permissions-group-new,.mm-permissions-user-new', this)
          .closest('tr')
          .hide()
          .next()
          .addBack()
          .addClass('mm-permissions-new-row');
        $('.mm-permissions-group-new', this)
          .closest('tr')
          .addClass('mm-permissions-group-new');
        $('.mm-permissions-user-new', this)
          .closest('tr')
          .addClass('mm-permissions-user-new');
        var form = $(this).closest('form');

        // on submit, process collected data into single fields
        form.once('mm-permissions').each(function() {
          form.submit(function () {
            if (form.find('#edit-no-save').val() * 1) {
              $('.mm-permissions-all-values-user', this).val('');
              $('.mm-permissions-all-values-group', this).val('');
              return;
            }
            var removed = {user: [], group: []};
            $('.mm-permissions', form).each(function() {
              var data = {user: '', group: ''}, used = {user: [], group: []};
              $(this)
                .find('.mm-permissions-data-row :checkbox:checked:enabled,.mm-permissions-data-row :input[type=hidden]')
                .each(function () {
                  var id = this.name.match(/(?:^|\[)(\w+)-(\w)-(\w+)/);
                  if (id && id[3] != 'everyone') {
                    data[id[1]] += id[2] + id[3];
                    used[id[1]][id[3]] = true;
                  }
                });
              var parent = $(this).parents('details');
              $('.mm-permissions-all-values-user', parent).val(data.user);
              $('.mm-permissions-all-values-group', parent).val(data.group);
              data = null;
              $(this)
                .find('.mm-permissions-data-row :checkbox[name*=-' + drupalSettings.MM.settings_perms.disabled_selector + '-]:not(.mm-permissions-disabled:checked)')
                .each(function () {
                  var id = this.name.match(/(?:^|\[)(\w+)-\w-(\w+)/);
                  if (id && id[2] != 'everyone' && !used[id[1]][id[2]]) {
                    removed[id[1]].push(this);
                  }
                });
            });
            var usersLen = removed.user.length;
            var all = $.merge(removed.user, removed.group);
            if (all.length) {
              var container = $('.mm-permissions:first').closest('details');
              if (!container.length) {
                container = $('.mm-permissions:first').closest('div');
              }
              container.closest('details[open!=open]').find('a:first').click();
              var conf;
              if (usersLen && removed.group.length) {
                conf = Drupal.t('Note: @indiv individual(s) and @grps group(s) will be removed, because "All Users" already have these same permissions.', {'@indiv' : usersLen, '@grps' : removed.group.length});
              }
              else if (usersLen) {
                conf = Drupal.t('Note: @indiv individual(s) will be removed, because "All Users" already have these same permissions.', {'@indiv' : usersLen});
              }
              else {
                conf = Drupal.t('Note: @grps group(s) will be removed, because "All Users" already have these same permissions.', {'@grps' : removed.group.length});
              }
              all = $(all).closest('tr');
              $('form#' + drupalSettings.MM.settings_perms.form_id + ' .messages').remove();
              var msg = $('<div class="messages messages--status"><h2>' + conf + '</h2><input type="button" value="' + Drupal.t('Continue Anyway') + '"></div>').prependTo(container);
              $(window).scrollTop(container.offset().top);
              $(':button', msg).click(function() {
                all.remove();
                form.find('#' + drupalSettings.MM.settings_perms.submit_id).click();
              });
              var oldColor = all.css('background-color'), done;
              all.css('background-color', '#fcc').fadeTo(200, 0.25).fadeTo(200, 1).fadeTo(200, 0.25,
                function() {
                  if (!done) {
                    all.css('background-color', oldColor);
                    done = true;
                  }
                }).fadeTo(200, 1);
              return false;
            }
          });
        });
        // fire all the checkbox change events, to handle disabling
        $(':checkbox:checked', this).each(function () {
          $(this).trigger('change');
        });
      });
    }
  };

  Drupal.MMSettingsUpdateSummary = function (obj) {
    $(obj).closest('details.vertical-tabs__pane').trigger('summaryUpdated');  // update any details summary
  };

  Drupal.MMSettingsPermsDelete = function (obj) {
    $(obj).closest('tr').fadeOut(500, function () {
      var sib = $(this).siblings(':first');
      $(this).remove();
      Drupal.MMSettingsUpdateSummary(sib);
    });
    $('form#' + drupalSettings.MM.settings_perms.form_id + ' .messages').remove();
    return false;
  };

  Drupal.MMSettingsPermsAddUsers = function (mmListObj, link_id) {
    if (mmListObj.length) {
      var context = $('#' + link_id).closest('.mm-permissions');
      $('form#' + drupalSettings.MM.settings_perms.form_id + ' .messages').remove();
      Drupal.mmDialogClose();
      var i = 1, matches = mmListObj.val().split(/(.*?)\{(.*?)\}/);
      var hidden_row = $('tr.mm-permissions-user-new:hidden', context);
      var obj = $('.mm-permissions [id^=edit-user-' + drupalSettings.MM.settings_perms.row_selector + '-]:checkbox:visible:not([id=edit-user-' + drupalSettings.MM.settings_perms.row_selector + '-owner]):last', context).closest('tr');
      // If no previous row or limit_write is set, use default row as source of copy
      if (!obj.length || $('[name=limit_write_not_admin]').length) obj = hidden_row;
      var new_row = [], dups = [];
      for (; i < matches.length; i += 3) {
        var dup = $('.mm-permissions-data-row :checkbox[name=user-' + drupalSettings.MM.settings_perms.row_selector + '-' + matches[i] + '],.mm-permissions-data-row :input[type=hidden][name=user-w-' + matches[i] + ']', context);
        if (dup.length) {
          $.merge(dups, dup);
        }
        else {
          $.merge(new_row,
            $(obj)
              .clone(true)
              .addClass('mm-permissions-new-row')
              .addClass('mm-permissions-data-row')
              .find('td:eq(0) div') // set user's name
                .html(matches[i + 1])
                .end()
              .find(':checkbox,:input[type=hidden]')  // rename checkboxes using uid
                .each(function () {
                  $(this).attr('name', this.name.replace(/(\w+-\w-)\w+(?=\]|$)/, '$1' + matches[i]));
                })
                .end()
              .fadeTo(0, 0)
          );
        }
      }
      mmListObj[0].delAll();
      $(mmListObj[0].mmList.p.autoCompleteObj).val('');
      Drupal.MMSettingsAnimateDups(dups);
      if (new_row.length) {
        // wait for modal to close
        setTimeout(function () {
          $('#edit-node-everyone', context).prop('checked', false);
          $(new_row)
            .insertBefore(hidden_row)
            .show()
            .fadeTo(500, 1);  // fadeIn doesn't work correctly with TRs
          if (obj == hidden_row) {
            // fire all the checkbox change events, to handle disabling
            $(':checkbox:checked', hidden_row.closest('table')).each(function () {
              $(this).trigger('change');
            });
          }
          Drupal.MMSettingsUpdateSummary(hidden_row);
        }, 250);
      }
    }
    else {
      Drupal.mmDialogClose();
    }
    return false;
  };

  Drupal.MMSettingsPermsAddGroup = function (activator) {
    window.mmListInstance = {
      addFromChild: function (chosen, info) {
        var context = $(activator).closest('.mm-permissions');
        var mmtid = chosen.id.substr(5);
        var hidden_row = $('tr.mm-permissions-group-new:hidden', context);
        var obj = $('.mm-permissions [id^=edit-group-' + drupalSettings.MM.settings_perms.row_selector + '-]:checkbox:visible:not([id=edit-group-' + drupalSettings.MM.settings_perms.row_selector + '-everyone]):last', context).closest('tr');
        // If no previous row or limit_write is set, use default row as source of copy
        if (!obj.length || $('[name=limit_write_not_admin]').length) obj = hidden_row;
        var dups = $('.mm-permissions-data-row :checkbox[name=group-' + drupalSettings.MM.settings_perms.row_selector + '-' + mmtid + '],.mm-permissions-data-row :input[type=hidden][name=group-w-' + mmtid + ']', context);
        if (dups.length) {
          Drupal.mmDialogClose();
          Drupal.MMSettingsAnimateDups(dups);
        }
        else {
          var new_row = $(obj)
            .clone(true)
            .addClass('mm-permissions-new-row')
            .addClass('mm-permissions-data-row')
            .find('td:eq(0)')
              .find('details') // make sure details is collapsed
                .attr('open', null)
                .end()
              .find('summary') // copy group name
                .text($('a:first', chosen).text().replace(/^\s*(.*?)\s*$/, '$1'))   // IE doesn't support trim()
                .end()
              .find('summary + div div') // copy user list
                .html(info)
                .show()
                .end()
              .end()
            .find(':checkbox,:input[type=hidden]')  // rename checkboxes using mmtid
              .each(function () {
                this.name = this.name.replace(/(\w+-\w-)\w+(?=\]|$)/, '$1' + mmtid);
              })
              .end()
            .fadeTo(0, 0);
          $('form#' + drupalSettings.MM.settings_perms.form_id + ' .messages').remove();
          $('details', new_row)
            .attr('open', null)
            .find('>summary')
              .unbind('click');
          Drupal.behaviors.collapse.attach(new_row, drupalSettings);
          Drupal.behaviors.MMDialog.attach(new_row, drupalSettings);
          Drupal.mmDialogClose();
          // wait for modal to close
          setTimeout(function () {
            $('#edit-node-everyone', context).prop('checked', false);
            new_row
              .insertBefore(hidden_row)
              .show()
              .fadeTo(500, 1);
            Drupal.MMSettingsUpdateSummary(hidden_row);
          }, 250);
        }
      }
    };
  };

  Drupal.MMSettingsAnimateDups = function (dups) {
    dups = $(dups).closest('tr');
    if (dups.length) {
      // wait for modal to close
      setTimeout(function () {
        var old = dups.css('background-color');
        dups.css('background-color', '#ff5').fadeTo(200, 0.25).fadeTo(200, 1).fadeTo(200, 0.25).fadeTo(200, 1).fadeTo(200, 0.25).fadeTo(200, 1, function () {$(this).css('background-color', old)});
      }, 250);
    }
  };

  Drupal.MMSettingsPermsOwner = function (mmListObj) {
    if (mmListObj.length) {
      var matches = mmListObj.val().split(/(.*?)\{(.*?)\}/);
      if (matches.length > 1) {
        $('.mm-permissions .settings-perms-owner-name').text(matches[2]);
        $('[name=owner]').val(matches[1]);
      }
    }
    Drupal.mmDialogClose();
    return false;
  };
})(jQuery, Drupal, drupalSettings);