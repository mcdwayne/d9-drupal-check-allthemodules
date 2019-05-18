if (typeof HTMLDivElement == 'object') HTMLDivElement.prototype.mmListArgs = [];

(function ($, Drupal, drupalSettings) {

mmList = function (parms) {
  this.p = parms;
  this.oneRow = parms.minRows <= 1 && parms.maxRows == 1;
  var pf = this.pf = this;

// left column
  if (parms.reInit) {
    this.leftTD = $('[name=leftTD]', parms.outerDiv)[0];
    this.rtTD = $('[name=rtTD]', parms.outerDiv)[0];
    return;
  }

  this.leftTD = $('<td name="leftTD" valign="top" />')
    .width(parms.flags.narrow_actions ? '99%' : '25%');
  if (!parms.flags.readonly) this.leftTD.css('whiteSpace', 'nowrap');
  if (parms.flags.hide_left_pane) this.leftTD.hide();
  parms.listObjDiv.siblings('label')
    .appendTo(this.leftTD);
  this.leftTD = this.leftTD[0];

// right column
  this.rtTD = $('<td name="rtTD" />');
  if (!parms.flags.action_under && !parms.flags.hide_left_pane) this.rtTD.css({
    width: '50%',
    'border-left': '1px solid #ccc',
    'padding-left': '7px',
    'vertical-align': 'top'
  });
  this.rtTD = this.rtTD[0];
  if (this.haveActions = parms.labelAddCat || parms.labelAddURL || parms.labelAddList || parms.labelReplace || parms.labelDelete) {
    $('<label />')
      .html(parms.labelAboveActions)
      .appendTo(this.rtTD);
    this.urlForm = {};
    if (parms.labelAddQuery && parms.inputDivID3) {   // Add a query button
      this.urlForm.query = $('#' + parms.inputDivID3);
      this.addButton(parms.labelAddQuery,
          function() {    // switch to Query add form
            pf.showURLForm('query');
            return false;
          },
          {'class': 'mm-list-add-button'})
        .appendTo(this.rtTD);
    }
    if (parms.labelAddURL && parms.inputDivID) {   // Add using url button
      this.urlForm.url = $('#' + parms.inputDivID);
      this.addButton(parms.labelAddURL,
          function() {    // switch to URL add form
            pf.showURLForm('url');
            return false;
          },
          {'class': 'mm-list-add-button'})
        .appendTo(this.rtTD);
    }
    jQuery.each(this.urlForm, function(obj_key, obj) {
      obj.hide();
      $(':submit', obj)
        .addClass('mmListNoDisable')
        .each(function(n) {
          if (n == 0) {        // OK
            $(this).click(function(event) {
              if ($(event.currentTarget).is(':hidden')) {
                // User hit Return on the keyboard in another sub-form
                return false;
              }
              if (pf.editURLField.value) {
                var v, info, name;
                if (obj_key == 'query') {
                  v = pf.editURLField.value;
                  info = name = pf.editNameField.value;
                }
                else {
                  v = pf.editURLField.value =
                     pf.editURLField.value.match(/^\s*(.*?)\s*$/)[1];
                  info = v.replace(/\/(?!\/)/g,'/&#8203;');
                  var n = v.match(/:\/+(.*?)\//);
                  name = n && n[1] ? n[1] + '...' : (v.length > 30 ? v.substr(0, 30) + '...' : v);
                }

                if (pf.editReplace) {
                  if (pf.p.replaceCallback) {
                    pf.editReplace[0].mmListArgs = [ name, obj_key, v, info ];
                    var ret = pf.p.replaceCallback(pf, pf.editReplace);
                    if (pf.info) pf.info.html(ret);
                  }
                  pf.setSelected(pf.editReplace);
                  pf.editReplace = null;
                }
                else {
                  pf.addItem(true, name, obj_key, v, info);
                }
                pf.setHiddenElt();
              }
              $('.mmListSubformDisabled', obj[0].form.parentNode)
                .attr('disabled', false)
                .removeClass('mmListSubformDisabled');
              obj.hide();
              $(pf.p.outerDiv).show();
              if (pf.editURLField.value) {
                pf.submitOnAdd();
              }
              return false;
            });
          }
          else if (n == 1) {    // Cancel
            $(this).click(function() {
              pf.editReplace = null;
              $('.mmListSubformDisabled', obj[0].form.parentNode)
                .attr('disabled', false)
                .removeClass('mmListSubformDisabled');
              obj.hide();
              $(pf.p.outerDiv).show();
              return false;
            });
          }
        });
    });

    if (parms.labelAddCat) {   // Add using cat button
      this.addButton(parms.labelAddCat,
          function() {
            pf.insertPoint = pf.selectedObj;
            pf.editReplace = null;
            pf.openWind(pf.p.popupURL);
            return false;
          },
          {'class': 'mm-list-add-button'})
        .appendTo(this.rtTD);
    }

    if (parms.labelAddList) {   // Add a set of input fields or "using free tag" button
      if (parms.inputDivID2) {   // "using free tag" button
        this.taxForm = $('#' + parms.inputDivID2)
          .hide()[0];
        $(':submit', this.taxForm)
          .addClass('mmListNoDisable')
          .each(function(n) {
            if (n == 0) {        // OK
              $(this).click(function() {
                var ok = false;
                if (pf.editTaxField.value) {
                  var v = pf.editTaxField.value, name = pf.editTaxText.value;
                  if (pf.editReplace) {
                    if (pf.p.replaceCallback) {
                      pf.editReplace[0].mmListArgs = [ name, 'taxon', v, '' ];
                      var ret = pf.p.replaceCallback(pf, pf.editReplace);
                      if (pf.info) pf.info.html(ret);
                    }
                    pf.setSelected(pf.editReplace);
                    pf.editReplace = null;
                  }
                  else {
                    var found = null;
                    $(pf.leftTD)
                      .children(':not(label)')
                      .each(function() {
                        if (this.mmListArgs[1] == 'taxon' && this.mmListArgs[2] == v) {
                          found = $(this);
                          return false;
                        }
                      });
                    if (found) pf.setSelected(found);
                    else {
                      pf.addItem(true, name, 'taxon', v, '');
                      ok = true;
                    }
                  }
                  pf.setHiddenElt();
                }
                $('.mmListSubformDisabled', pf.taxForm.form.parentNode)
                  .attr('disabled', false)
                  .removeClass('mmListSubformDisabled');
                $(pf.taxForm).hide();
                $(pf.p.outerDiv).show();
                if (ok) {
                  pf.submitOnAdd();
                }
                return false;
              });
            }
            else if (n == 1) {    // Cancel
              $(this).click(function() {
                pf.editReplace = null;
                $('.mmListSubformDisabled', pf.taxForm.form.parentNode)
                  .attr('disabled', false)
                  .removeClass('mmListSubformDisabled');
                $(pf.taxForm).hide();
                $(pf.p.outerDiv).show();
                return false;
              });
            }
          });
        this.editTaxField = $('input:hidden', this.taxForm)[0];
        this.editTaxText = $('input:text', this.taxForm)[0];
        this.editTaxSample = $('#tax-sample', this.taxForm)[0];
        this.addButton(parms.labelAddList,
            function() {    // switch to free tag input form
              pf.showTaxForm();
              return false;
            },
            {'class': 'mm-list-add-button'})
          .appendTo(this.rtTD);
        this.showTaxForm = function(item) {
          $(':submit:not(.mmListNoDisable)', this.p.hiddenElt.form)
            .add('.ui-dialog-buttonset :button', this.p.hiddenElt.form.parentNode)
            .attr('disabled', true)
            .addClass('mmListSubformDisabled');
          this.insertPoint = this.selectedObj;
          $(pf.p.outerDiv).hide();
          $(pf.taxForm).show();
          this.editTaxField.value = item ? item.mmListArgs[2] : '';
          this.editTaxText.value = item ? item.mmListArgs[0] : '';
          this.editTaxSample.innerHTML = item ? item.mmListArgs[0] : Drupal.t('(choose a tag)');
          this.editTaxText.parentFunc = this;
          this.editTaxText.focus();
        };
      }
      else {    // set of input fields
        parms.listObjDiv
          .remove()
          .hide();
        this.addButton(parms.labelAddList,
            function() {
              pf.insertPoint = pf.selectedObj;
              pf.editReplace = null;
              pf.addItem(true);
              return false;
            },
            {'class': 'mm-list-add-button'})
          .appendTo(this.rtTD);
      }
    }

    $(':button:not(:last)', this.rtTD).after('<br />');

    if (parms.labelReplace) {   // Replace button
      this.editBut = this.addButton(parms.labelReplace,
          function() {
            pf.editReplace = pf.selectedObj;
            pf.openWind(pf.editReplace[0].mmListArgs[2]);
            return false;
          },
          {'disabled': true});
      this.editBut.appendTo(this.rtTD);
    }

    if (parms.labelDelete) {   // Delete button
      this.delBut = this.addButton(parms.labelDelete,
          function(evnt) {
            this.blur();
            if (evnt.shiftKey || !pf.p.delConfirmMsg || confirm(pf.p.delConfirmMsg))
                pf.delSelected();
            return false;
          },
          {'disabled': true});
      this.delBut.appendTo(this.rtTD);
    }
  }

  if (Drupal.autocomplete && parms.autoCompleteObj) {  // TODO: possibly add parms.inputDivID2 for taxonomy autocomplete?
    var $auto_obj = $(parms.autoCompleteObj);
    if (!this.haveActions) $auto_obj.parent().css('marginTop', 0);
    $auto_obj.parent().appendTo(this.rtTD);
    parms.autoCompleteObj.parentFunc = this;
    if (!$auto_obj.data('ui-autocomplete')) {
      $auto_obj.autocomplete(Drupal.autocomplete);
    }
    // Initialize, if mm_search_replace.js has just instantiated the field.
    if (typeof Drupal.behaviors.autocomplete !== 'undefined') {
      Drupal.behaviors.autocomplete.attach($auto_obj[0].parentNode, drupalSettings);
    }
    /**
     * The default code in autocomplete.js parses the input field and separates
     * multiple terms on commas. We don't want to do this, since it could
     * interfere with names like "Smith, John".
     */
    $auto_obj
      /**
       * Puts the currently highlighted suggestion into the autocomplete field.
       * Overrides the function in autocomplete.js.
       * Handles an autocompleteselect event.
       *
       * @param {jQuery.Event} event
       *   The event triggered.
       * @param {object} ui
       *   The jQuery UI settings object.
       *
       * @return {boolean}
       *   Returns false to indicate the event status.
       */
      .autocomplete('option', 'select',
        function(event, ui) {
          var ac_val = ui.item.value;
          if (this.parentFunc) {
            var pf = this.parentFunc;
            if (pf.editTaxText == this) {
              pf.editTaxSample.innerHTML = this.value = ui.item.label;
              pf.editTaxField.value = ac_val;
              return true;
            }
            // When the input field becomes disabled, the popup hangs around.
            var i = ac_val.indexOf('-');
            if (i > 0) {
              var name = ac_val.substr(i + 1);
              var mmtid = ac_val.substring(0, i);
              event.target.value = '';
              var found;
              $(pf.leftTD)
                .children(':not(label)')
                .each(function() {
                  if (this.mmListArgs[1] == mmtid) {
                    found = $(this);
                    return false;
                  }
                });
              if (found) {
                pf.setSelected(found);
                return false;
              }

              if (!pf.haveActions || pf.oneRow) {
                var sel = pf.selectedObj;
                if (!sel) sel = $(pf.leftTD).children(':eq(1)');
                sel[0].mmListArgs = [ name, mmtid, '', '' ];
                if (pf.p.replaceCallback) pf.p.replaceCallback(pf, sel);
              }
              else {
                pf.addItem(false, name, mmtid, '', '');
                pf.setSelected();
              }
              pf.setHiddenElt();
              pf.submitOnAdd(this);
              $(pf.p.outerDiv)    // update any details summary
                .closest('details.vertical-tabs__pane')
                .add('details#edit-settings-perms')
                .trigger('summaryUpdated');
            }
          }
          // Return false to tell jQuery UI that we've filled in the value already.
          return false;
        }
      )
      /**
       * The search handler is called before a search is performed.
       *
       * @function Drupal.autocomplete.options.search
       *
       * @param {object} event
       *   The event triggered.
       *
       * @return {bool}
       *   Whether to perform a search or not.
       */
      .autocomplete('option', 'search',
        function(event) {
          // Only search when the term is at least the minimum length of 2.
          return event.target.value.length >= 2;
        }
      );
  }
  else if (parms.labelAboveInfo) {
    this.infoDiv = $('<div />')   // Info
      .hide()
      .css('whiteSpace', 'normal')   // maybe needed?
      .append($('<label />')
        .html('<br />' + parms.labelAboveInfo))
      .append(this.info = $('<div />')
        .addClass('description'));
    this.infoDiv.appendTo(this.rtTD);
  }

  // TRs go into a TBODY
  var tbody = $('<tbody />');

  // both TDs go into a TR
  var tr = $('<tr />')
    .append(this.leftTD)
    .appendTo(tbody);
  if (parms.flags.action_under) {
    $('<tr />')
      .append(this.rtTD)
      .appendTo(tbody);
  }
  else tr.append(this.rtTD);

  // TBODY goes into TABLE, which is appended to outer DIV
  $('<table />')
    .width('100%')
    .append(tbody)
    .appendTo(parms.outerDiv);

  if (parms.selectCallback && !pf.oneRow)
    $(this.leftTD).click(function() {  // clicked in leftTD: unselect
      pf.setSelected();
      return true;
    });

  if (parms.imgPath && !parms.flags.readonly) {
    var od = this.opsDiv = $('<div />')
      .addClass('mmlist-opsdiv')
      .css({'clear': 'both', 'float': 'right', 'padding-left': '3px'});
    var addImg = function(path, title, id, func) {
      if (!title) return;
      $('<img />')
        .attr({
          id: 'mmBut' + id,
          src: parms.imgPath + '/' + path,   // activeSrc?
          title: title
        })
        .bind('mmButEnabled', {}, func || function() {})
        .appendTo(od);
    };
    addImg('top.gif', parms.labelTop, 0);
    addImg('up.gif', parms.labelUp, 1);
    addImg('del.gif', parms.labelX, 2);
    $('<br />').appendTo(od);
    addImg('bott.gif', parms.labelBott, 3);
    addImg('down.gif', parms.labelDown, 4);
    addImg('edit.gif', parms.labelEdit, 5);
  }

  if (parms.flags.initial_focus) {
    $('[name="' + parms.flags.initial_focus + '"]', tbody).focus();
  }
};

mmList.prototype = {
  addButton: function(label, onclick, attr) {
    attr = attr || false;
    return $('<input type="button"/>')
      .val(label)
      .attr(attr)
      .click(onclick);
  },

  showURLForm: function(type, item) {
    $(':submit:not(.mmListNoDisable)', this.p.hiddenElt.form)
      .add('.ui-dialog-buttonset :button', this.p.hiddenElt.form.parentNode)
      .attr('disabled', true)
      .addClass('mmListSubformDisabled');
    this.insertPoint = this.selectedObj;
    $(this.p.outerDiv).hide();

    var t = $(':text', this.urlForm[type]);
    if (!t.length) return;

    if (type == 'query') {
      this.editNameField = t[0];
      this.editNameField.value = item ? item.mmListArgs[3] : '';
      t = $('[type=textarea]', this.urlForm[type]);
      if (!t.length) return;
      this.editURLField = t[0];
      this.editURLField.value = item ? item.mmListArgs[2] : '';
      $(this.urlForm[type]).show();
      this.editNameField.focus();
    }
    else {
      this.editURLField = t[0];
      this.editURLField.value = item ? item.mmListArgs[2] : '';
      $(this.urlForm[type]).show();
      this.editURLField.focus();
    }
  },

  addItem: function(select) {
    if (!this.leftTD || this.p.maxRows > 0 &&
        this.leftTD.childNodes.length - 1 >= this.p.maxRows) return;
    var ndiv = this.p.listObjDiv.clone(true);
    var pf = this;
    ndiv[0].mmListArgs = [];
    for (var i = arguments.length; --i >= 1;)
      ndiv[0].mmListArgs[i - 1] = arguments[i];
    if (!this.p.flags.readonly)
      ndiv.click(function(evnt) {
        var target = $(evnt.target);
        var disabled = target.attr('disabled');
        if (evnt.target.tagName == 'IMG' && !(typeof disabled == 'string' ? disabled == 'true' : disabled)) {
          var item = target.parents(':eq(1)');
          var update = true;
          var parent = $(pf.leftTD);
          switch (evnt.target.id) {
            case 'mmBut0': // top
              item.insertAfter(parent.children(':eq(0)'));
              break;

            case 'mmBut1': // up
              item.insertBefore(item.prev());
              break;

            case 'mmBut2': // delete
              pf.setSelected(item);
              if (evnt.shiftKey || !pf.p.delConfirmMsg || confirm(pf.p.delConfirmMsg)) {
                if (pf.p.minRows > 0 && pf.leftTD.childNodes.length - 1 <= pf.p.minRows)
                    pf.clearRow(item[0]);
                else item.remove();
                item = null;
              }
              else update = false;
              break;

            case 'mmBut3': // bottom
              item.appendTo(parent);
              break;

            case 'mmBut4': // down
              item.insertAfter(item.next());
              break;

            case 'mmBut5': // edit
              pf.editReplace = item;
              if (item[0].mmListArgs[1] == 'url' || item[0].mmListArgs[1] == 'query') pf.showURLForm(item[0].mmListArgs[1], item[0]);
              else if (item[0].mmListArgs[1] == 'taxon') pf.showTaxForm(item[0]);
              else pf.openWind(item[0].mmListArgs[2]);

              update = false;
              break;
          }

          if (update) {
            pf.setSelected(item || null);
            pf.enableOpts();
            pf.setHiddenElt();
          }
        }
        else if (pf.p.selectCallback) {
          this.blur();
          if (!pf.oneRow) pf.toggleSelected.call(pf, $(this));
        }
        else return true;

        return false;
      });
    if (this.p.addCallback) this.p.addCallback(this, ndiv);

    ndiv.show();
    if (this.oneRow || select) this.setSelected(ndiv);
    else if (this.p.selectCallback) this.p.selectCallback(this, ndiv, false);

    mmListInsertBefore(this.leftTD, ndiv[0], this.insertPoint);
    this.insertPoint = null;
    if (select) this.enableOpts();
    this.setHiddenElt();

    return ndiv[0];
  },

  submitOnAdd: function(obj) {
    if (this.p.flags.submit_on_add) {
      if (obj) {
        var popup = obj.popup;
        if (popup) {
          obj.popup = null;
          $(popup).remove();
        }
        obj.selected = false;
      }
      var form = $(this.rtTD).closest('form');
      var button = form.find(':button');
      if (button.length == 1) button.click();
      else form.submit();
    }
  },

  enableFuncs: [
    function(i) { return i > 1; },  // top
    function(i) { return i > 0; },  // up
    null,
    function(i, pflen) { return i < pflen - 3; },
    function(i, pflen) { return i < pflen - 2; },
    null ],

  enableOpts: function() {
    var pf = this;
    $(this.leftTD).children(':not(label)').each(function(i) {
      $('.mmlist-opsdiv', this)
        .children('img')
        .each(function() {
          var fn = pf.enableFuncs[this.id.substring(5)];
          if (fn) {
            var disable = fn(i, pf.leftTD.childNodes.length) ? undefined : true;
            var obj = $(this);
            if (disable != obj.attr('disabled')) {
              if (disable) {
                this.oldTitle = this.title;
                obj
                  .attr({
                    disabled: true,
                    title: ''})
                  .css('opacity', 0.15);
              }
              else {
                obj
                  .attr({
                    disabled: false,
                    title: this.oldTitle})
                  .css('opacity', 1);
              }
            }
          }
        });
      $(this).css('borderBottom',
          pf.p.flags.action_under && pf.rtTD.childNodes.length ?
          '1px solid #bfbe95' : 'none');
    });
  },

  unselect: function() {
    if (!this.p.selectCallback) return;

    var pf = this.pf;
    $(this.leftTD)
      .children(':not(label)')
      .each(function() {
        pf.p.selectCallback(pf, $(this), false);
      });
  },

  getSelected: function() {
    return this.selectedObj;
  },

  toggleSelected: function(obj) {
    if (this.selectedObj && obj && this.selectedObj[0] == obj[0]) obj = null;
    this.setSelected(obj);
  },

  setSelected: function(obj) {
    if (this.selectedObj != obj) {
      this.unselect();
      this.selectedObj = obj;
      if (obj) {
        var ret;
        if (this.p.selectCallback)
          ret = this.p.selectCallback(this.pf, obj, !this.oneRow);
        if (this.delBut) this.delBut.attr('disabled', false);
        if (this.editBut) this.editBut.attr('disabled', false);
        if (this.infoDiv) {
          if (ret == '') this.infoDiv.hide();
          else {
            this.info.html(ret);
            if (typeof Drupal.behaviors.MMDialog == 'function') Drupal.behaviors.MMDialog(document, drupalSettings);
            this.infoDiv.show();
          }
        }
      }
      else {
        if (this.delBut) this.delBut.attr('disabled', true);
        if (this.editBut) this.editBut.attr('disabled', true);
        if (this.infoDiv) this.infoDiv.hide();
      }

      if (this.p.maxRows > 0 && this.addBut)
        $(this.addBut).attr('disabled', this.leftTD.childNodes.length - 1 >= this.p.maxRows);
      this.insertPoint = null;
    }
  },

  clearRow: function(obj) {
    $(':input', obj)
      .unbind('change');
    $(':checkbox', obj).each(function() {
      $(this).prop('checked', this.defaultChecked);
    });
    $(':input:not(:checkbox)', obj).val('');
  },

  delSelected: function() {
    if (this.p.minRows > 0 && this.leftTD.childNodes.length - 1 <= this.p.minRows)
      this.clearRow(this.selectedObj);
    else if (this.oneRow) {
      this.selectedObj[0].mmListArgs = [ '', '', '', '' ];
      var ret = this.p.replaceCallback(this.pf, this.selectedObj);
      if (this.info) this.info.html(ret);
      if (this.infoDiv) this.infoDiv.hide();
      this.setHiddenElt();
      return;
    }
    else this.selectedObj.remove();

    this.setSelected();
    this.setHiddenElt();
  },

  addFromChild: function(obj, info, node, name) {
    if (!obj) return;

    var url = [ obj.id.substr(5) ];
    var longPath = [ $('a:first', obj).html().replace(/<(\w+).*?>.*?<\/\1>/g, '').replace(/^[\s\xA0]+/, '').replace(/[\s\xA0]+$/, '') ];
    $(obj).parents('li[id^=mmbr-]').each(function() {
      url.unshift(this.id.substr(5));
      longPath.unshift($('a:first', this).html().replace(/<(\w+).*?>.*?<\/\1>/g, '').replace(/^[\s\xA0]+/, '').replace(/[\s\xA0]+$/, ''));
    });

    if (!isNaN(info)) info = longPath.join('&nbsp;&raquo; ');

    var arg1 = this.taxForm ? 'cat' : url[url.length - 1];
    if (node) arg1 += '/' + node;
    else if (!name) name = longPath[longPath.length - 1];

    Drupal.mmDialogClose();
    var ok = false;
    if (this.editReplace) {
      if (this.p.replaceCallback) {
        this.editReplace[0].mmListArgs = [ name, arg1, url.join('/'), info ];
        var ret = this.p.replaceCallback(this.pf, this.editReplace);
        if (this.info) this.info.html(ret);
        if (this.infoDiv) this.infoDiv.show();
      }
      this.setSelected(this.editReplace);
      this.editReplace = null;
      ok = true;
    }
    else {
      var pf = this;
      var path = url.join('/');
      var found = null;
      $(this.leftTD)
        .children(':not(label)')
        .each(function() {
          if (pf.taxForm ? this.mmListArgs[1] == arg1 && this.mmListArgs[2] == path : this.mmListArgs[1] == url[url.length - 1]) {
            found = $(this);
            return false;
          }
        });

      if (found) this.setSelected(found);
      else {
        this.addItem(true, name, arg1, path, info);
        ok = true;
      }
    }
    this.setHiddenElt();
    if (ok) {
      this.submitOnAdd();
    }
  },

  setHiddenElt: function() {
    if (!this.p.flags.readonly) {
      var count = 0;
      var val = '';
      var pf = this.pf;
      $(this.leftTD)
        .children(':not(label)')
        .each(function() {
          var ret;
          if (ret = pf.p.dataCallback(pf, $(this))) {
            val += ret;
            count++;
          }
        });
      $(this.p.hiddenElt).val(val);
      if (count) {
        if (this.p.updateOnChangeName) {
          var elt = $('[name="' + this.p.updateOnChangeName + '"]');
          if (elt.length)
            if (elt[0].type == 'checkbox') elt[0].checked = 0;
            else elt[0].value = '';
        }
        if (typeof(this.p.updateOnChangeCallback) == 'function') {
          this.p.updateOnChangeCallback(this.p);
        }
      }
      if (this.p.maxRows > 0) {
        if (this.p.autoCompleteObj && !this.oneRow) $(this.p.autoCompleteObj).attr('disabled', count >= this.p.maxRows);
        $('.mm-list-add-button', this.p.outerDiv).attr('disabled', count >= this.p.maxRows);
      }
      window.mmListInstance = null;
      if (typeof(MMSR_recalculate) == 'function') window.setTimeout(MMSR_recalculate, 0);  // do this, instead of calling the func directly, due to a FF bug having to do with the iframe from which this code is called
    }
  },

  dialogInstance: 1000,

  openWind: function(url) {
    window.mmListInstance = this;
    Drupal.mmDialogAdHoc(this.p.popupBase + url, this.p.popupLabel, {iframe: true, fullSize: true});
  }
};

mmListInsertBefore = function (parent, newnode, oldnode) {
  return oldnode ? $(oldnode).before(newnode) : $(parent).append(newnode);
};

mmListImport = function (pf, value) {
  var matches = value.split(/(.*?)\{(.*?)\}/);
  var i = 0;
  $(pf.leftTD)
    .children(':not(label)')
    .each(function() {
      this.mmListArgs = [ matches[i + 2], matches[i + 1], '', '' ];
      if (pf.p.replaceCallback) pf.p.replaceCallback(pf, $(this));
      i += 3;
    });
  for (; i < matches.length - 1; i += 3)
    pf.addItem(false, matches[i + 2], matches[i + 1], '', '');
};

mmListGetObj = function (where, listObjDivSelector, outerDivSelector, hiddenName, autoName, parms) {
  parms.listObjDiv = $(listObjDivSelector, where);
  var outerDiv = $(outerDivSelector, where);
  parms.outerDiv = outerDiv[0];
  if (!parms.outerDiv) return;

  if (where != $ && $(where).hasClass('subpanel-inited')) {
    parms.hiddenElt = $('.mm-list-hidden', outerDiv)[0];
    if (typeof parms.hiddenElt.mmList == 'object') return;
    parms.reInit = true;
  }
  else {
    parms.hiddenElt = $('<input type="hidden" name="' + hiddenName + '" class="mm-list-hidden" />')
      .appendTo(outerDiv)[0];
  }
  parms.autoCompleteObj = autoName ? $('input[name="' + autoName + '"]', where)[0] : null;

  if (!parms.listObjDiv.length || !parms.hiddenElt) return;

  parms.hiddenElt.delAll = function() {  // called by external onclick events, like when user clicks on a checkbox elsewhere on the form
    if (this.mmList.oneRow) this.mmList.delSelected();
    else {
      $(this.mmList.leftTD)
        .children(':not(label)')
        .remove();
      this.mmList.setSelected();
      this.mmList.setHiddenElt();
    }
  };

  $.each(['addCallback', 'replaceCallback', 'selectCallback', 'dataCallback', 'updateOnChangeCallback'], function(key, callback) {
    if (parms[callback] && typeof parms[callback] !== 'function') {
      parms[callback] = mmListCallbacks[parms[callback]];
    }
  });

  return parms.hiddenElt.mmList = new mmList(parms);
};

mmListCallbacks = {
  //------- Category callbacks -------
  catAddCallback: function (pf, ndiv) {
    // args: 0=text, 1=mmtid, 2=url, 3=longPath
    mmListCallbacks.catReplCallback(pf, ndiv);
    if (pf.opsDiv) mmListInsertBefore(ndiv, pf.opsDiv.clone(true), $(':first-child', ndiv));
    mmListCallbacks.catSelectCallback(pf, ndiv, false);
  },
  catSelectCallback: function (pf, ndiv, state) {
    var cls = state ? 'mm-list-selected' : 'mm-list';
    ndiv.attr('class', cls);
    $(':first-child', ndiv).attr('class', cls);
    return ndiv[0].mmListArgs[3];
  },
  catReplCallback: function (pf, ndiv) {
    $(':first-child', ndiv).html(ndiv[0].mmListArgs[0]);
    return ndiv[0].mmListArgs[3];
  },
  catDataCallback: function (pf, ndiv) {
    if (!ndiv[0].mmListArgs[1]) return false;
    return ndiv[0].mmListArgs[1] + '{' + ndiv[0].mmListArgs[0] + '}';
  },

  //------- RSS Page callbacks -------
  rssAddCallback: function (pf, ndiv) {
    // args: 0=name, 1=type, 2=data, 3=longPath
    if (pf.opsDiv) {
      mmListInsertBefore(ndiv, pf.opsDiv.clone(true), $(':first-child', ndiv));
      if (navigator.userAgent.indexOf('Firefox') >= 0) ndiv.children(':eq(1)').css('marginRight', '53px');
    }
    mmListCallbacks.rssReplCallback(pf, ndiv);
    mmListCallbacks.rssSelectCallback(pf, ndiv, false);
  },
  rssSelectCallback: function (pf, ndiv, state) {
    var cls = state ? 'mm-list-selected' : 'mm-list';
    ndiv.attr('class', cls);
    ndiv.children(':eq(1)').attr('class', cls);
    return ndiv[0].mmListArgs[3];
  },
  rssReplCallback: function (pf, ndiv) {
    ndiv.children(':eq(1)').html(ndiv[0].mmListArgs[0]);
    return ndiv[0].mmListArgs[3];
  },
  rssDataCallback: function (pf, ndiv) {
    return '{:' + ndiv[0].mmListArgs[0] + ':}{:' + ndiv[0].mmListArgs[1] + ':}{:' + ndiv[0].mmListArgs[2] + ':}';
  },

  //------- Generic List callbacks -------
  listAddCallback: function (pf, ndiv) {
    var radios = [], argsInd = -1;
    var readOnly = pf.p.flags.readonly;
    var args = ndiv[0].mmListArgs;
    $(':input', ndiv).each(function() {
      var roDiv;
      if (readOnly) {
        roDiv = $('<div />')
          .addClass(this.type)
          .attr({name: this.name, id: this.id});
      }
      else if (argsInd + 1 >= args.length) args[argsInd + 1] = '';

      if (this.type == 'radio') {
        this.name = '__radio/' + pf.leftTD.childNodes.length + '/' + this.name;
        var k;
        for (k = 0; k < radios.length; k++)
          if (radios[k] == this.name) break;
        if (k == radios.length) radios[++argsInd] = this.name;
        this.argsIndex = argsInd;
        if (readOnly) {
          roDiv.html(args[argsInd] == this.value ? '[X]' : '[&nbsp;]');
          if (args[argsInd] == this.value) roDiv.addClass('checked');
        }
        else this.checked = args[argsInd] == this.value;
      }
      else {
        this.argsIndex = ++argsInd;
        if (readOnly) {
          if (this.type == 'checkbox') {
            roDiv.html(args[argsInd] != '' ? '[X]&nbsp;' : '[&nbsp;]&nbsp;');
            if (args[argsInd] != '') roDiv.addClass('checked');
          }
          else if (this.tagName == 'SELECT') {
            this.value = args[argsInd] || '';
            roDiv.html(this.options[this.selectedIndex].innerHTML);
          }
          else if (args[argsInd]) roDiv.html(args[argsInd]);
        }
        else if (this.type == 'checkbox') this.checked = args[argsInd] != '';
        else this.value = args[argsInd] || '';
      }

      if (readOnly) roDiv.replaceAll(this);
      else $(this).change(function() {
        if (this.type == 'checkbox') args[this.argsIndex] = this.checked ? this.value : '';
        else if (this.type == 'radio') {
          if (this.checked) args[this.argsIndex] = this.value;
        }
        else args[this.argsIndex] = this.value;
        pf.setHiddenElt();
        return true;
      });
    });
    if (pf.opsDiv) mmListInsertBefore(ndiv, pf.opsDiv.clone(true), ndiv.children(':eq(0)'));
  },
  listDataCallback: function (pf, ndiv) {
    if (!ndiv[0].mmListArgs.length) return '';
    return '{:' + ndiv[0].mmListArgs.join(':}{:') + ':}';
  }
};

Drupal.behaviors.mmListInit = {
  attach: function (context) {
    $('.mm-template')
      .once('mm-template')
      .each(function() {
        $('body').append(this.innerHTML);
      });
    if (typeof Drupal.behaviors.autocomplete !== 'undefined') {
      Drupal.behaviors.autocomplete.attach($('body'), drupalSettings);
    }

    $.each(drupalSettings.MM.mmListInit, function(key, instance) {
      // Clone parms so that changes at lower levels don't affect other instances.
      instance.parms = jQuery.extend(true, {}, instance.parms);
      var obj = mmListGetObj(context, instance.listObjDivSelector, instance.outerDivSelector, instance.hiddenName, instance.autoName, instance.parms);
      if (obj && obj.addItem) {
        delete drupalSettings.MM.mmListInit[key];
        $.each(instance.add, function(key, list) {
          list.unshift(false);
          obj.addItem.apply(obj, list);
        });
        obj.enableOpts();
      }
      return obj;
    });
  }
};

})(jQuery, Drupal, drupalSettings);
