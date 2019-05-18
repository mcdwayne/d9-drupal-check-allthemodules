/**
 * @file
 * @author Falchenko Maxim aka be3
 * @plugin_page http://tops.net.ua/jquery_addtocopy/
 * @desc Adds a link to the copied text
 * @version 1.2
 * @example
 * $("#content").addtocopy();
 * @license free
 */

jQuery.fn.addtocopy = function (usercopytxt) {
  'use strict';
  var options = {htmlcopytxt: '<br>More: <a href="' + window.location.href + '">' + window.location.href + '</a><br>', minlen: 25, addcopyfirst: false};
  jQuery.extend(options, usercopytxt);
  var copy_sp = document.createElement('span');
  copy_sp.id = 'ctrlcopy';
  copy_sp.innerHTML = options.htmlcopytxt;
  return this.each(function () {
    jQuery(this).mousedown(function () {
      jQuery('#ctrlcopy').remove();
    });
    jQuery(this).mouseup(function () {
      // Good times.
      var slcted;
      var seltxt;
      var nslct;
      if (window.getSelection) {
        slcted = window.getSelection();
        seltxt = slcted.toString();
        if (!seltxt || seltxt.length < options.minlen) {
          return;
        }
        nslct = slcted.getRangeAt(0);
        seltxt = nslct.cloneRange();
        seltxt.collapse(options.addcopyfirst);
        seltxt.insertNode(copy_sp);
        if (!options.addcopyfirst) {
          nslct.setEndAfter(copy_sp);
        }
        slcted.removeAllRanges();
        slcted.addRange(nslct);
      }
      else if (document.selection) {
        // Bad times.
        slcted = document.selection;
        nslct = slcted.createRange();
        seltxt = nslct.text;
        if (!seltxt || seltxt.length < options.minlen) {
          return;
        }
        seltxt = nslct.duplicate();
        seltxt.collapse(options.addcopyfirst);
        seltxt.pasteHTML(copy_sp.outerHTML);
        if (!options.addcopyfirst) {
          nslct.setEndPoint('EndToEnd', seltxt); nslct.select();
        }
      }
    });
  });

};
