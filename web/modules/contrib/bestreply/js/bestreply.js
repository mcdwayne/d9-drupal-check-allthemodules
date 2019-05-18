/**
 * @file
 * Adds ajax functionality to bestreply module to Mark and clear best reply without page refresh.
 */

(function ($) {
  'use strict';
  // Namespace Object.
  var BestReply = BestReply || {};

  Drupal.behaviors.bestreply = {
    attach: function (context) {
      BestReply.rt = false;
      $('.br_mark, .br_clear:not(.br_processed)', context)
        .addClass('br_processed')
        .click(function () {
          var self = $(this);
          $.ajax({
            url: self.attr('href'),
            type: 'GET',
            data: {js: 'true'},
            dataType: 'json',
            timeout: 4000,
            success: function (json) {
              BestReply.brChange(self, json.action, json.cid);
            },
            error: function (json) {
              // Continue loading the page.
              BestReply.rt = true;
            }
          });
          return BestReply.rt;
        });
    }
  };

  /**
   * Change the links to suit the new bestreply.
   *
   * @param {string} ele, Link element that was clicked
   * @param {string} action, Action to perform (clear, replace, mark)
   * @param {int} cid, Comment Id
 */
  BestReply.brChange = function (ele, action, cid) {
    var bp = drupalSettings.path.baseUrl;
    var br_name = drupalSettings.bestreply.name;

    if (action === 'clear') {
      BestReply.setMark($('.br_clear'), bp + 'bestreply/mark/' + cid);
      // Remove the view link from node.
      $('.bestreply-view').remove();
      // Add class last to last element.
      $('.node .links li:last').not('.last').addClass('last');
      // Remove the last class when it's not on the last element.
      $('.node .links li.last').not(':last').removeClass('last');
    }
    else if (action === 'replace') {
      // Change the view link.
      $('.links .br_view').attr('href', '#comment-' + cid);

      // Change the href.
      var href = $('.br_clear').attr('href');
      var nhref;
      if (href) {
        nhref = href.replace('clear', 'mark');
      }
      else {
        nhref = '/bestreply/mark/' + drupalSettings.bestreply.ismarked;
      }
      BestReply.setMark($('.br_clear'), nhref);
      BestReply.setClear(ele, bp, cid);
    }
    else {
      // Mark
      // Add the View link to the node.
      $('.node ul.links:not(.comment .links)').append('<li class="bestreply-view"><a class="br_view" href="#comment-' + cid + '" title="Jump to the ' + br_name + '">View ' + br_name + '</a></li>');
      BestReply.setClear(ele, bp, cid);
      $('.node .links li:last').not('.last').addClass('last');
      $('.node .links li.last').not(':last').removeClass('last');
    }
  };

  /**
   * Set the link element to Clear.
   *
   * @param {string} ele, Link element to change
   * @param {string} bp, Base path
   * @param {int} cid, Comment Id
   * @param {string} br_name, Name used for best reply
 */
  BestReply.setClear = function (ele, bp, cid) {
    var br_name = drupalSettings.bestreply.name;
    $(ele).attr('href', bp + 'bestreply/clear/' + cid)
      .attr('title', 'Clear ' + br_name)
      .text('Clear ' + br_name)
      .removeClass('br_mark')
      .addClass('br_clear');
  };

  /**
   * Set the link element to Mark.
   *
   * @param {string} ele, Link element to change
   * @param {string} nhref, New url
   * @param {string} br_name, Name used for best reply
 */
  BestReply.setMark = function (ele, nhref) {
    var br_name = drupalSettings.bestreply.name;
    $(ele).attr('href', nhref)
      .removeClass('br_clear')
      .addClass('br_mark')
      .attr('title', 'Mark as the ' + br_name)
      .text(br_name);
  };
})(jQuery);
