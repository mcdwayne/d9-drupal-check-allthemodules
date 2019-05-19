/**
 * Javascript that shows history items when the history list item is clicked.
 */
(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.SmsHistoryList = {
    attach: function (context, settings) {

      var historyStore = new HistoryStore(drupalSettings.smsHistory.historyUrl);
      // Apply ajax on each tr when it is clicked.
      $('.sms-history-table', context).find('tbody > tr').once('sms-history-list')
        .on('click', function () {
          var item_id = $(this).find('td[data-history-id]').attr('data-history-id');
          historyStore.update(item_id, $('#sms-history'));
          $(this)
              .addClass('js-selected')
              .siblings().removeClass('js-selected');
        });

      // Since the first one is loaded from the backend, mark it with the selected class.
      $('.sms-history-table', context).find('tbody > tr').first().addClass('js-selected');
    }
  };

  var HistoryStore = (function () {

    function HistoryStore(url) {
      this.store = {};
      this.url = url;
    }

    HistoryStore.prototype.update = function(itemId, $element) {
      // If we have it cached already, use what's in the cache.
      if (this.store[itemId]) {
        $element.html(this.store[itemId]);
        return;
      }

      // Get the themed history from the backend.
      var _hstore = this;
      $.ajax({
        url: _hstore.url + '?item_id=' + itemId,
        error: function() {
          console.log('Item with ID ' + itemId + ' not found');
          $element.html(Drupal.t('<h4>Item not found</h4>'));
        },
        dataType: 'html',
        success: function(data) {
          _hstore.store[itemId] = data;
          $element.html(data);
        },
        type: 'GET'
      });
    };

    return HistoryStore;

  })();
  
})(jQuery, Drupal, drupalSettings);
