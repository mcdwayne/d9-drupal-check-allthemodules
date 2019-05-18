(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.createPager = {
    attach: function (context) {
      $(".skyfish__folder").each(function( index ) {
        var id = this.id;
        $("#" + id).append("<div id='" + id + "-pagination'></div>");
        var items = $("#" + id + " .details-wrapper div");
        var numItems = items.length;
        var perPage = drupalSettings.media_skyfish.pager.media_skyfish_items_per_page;
        items.slice(perPage).hide();
        $("#" + id + "-pagination").pagination({
          items: numItems,
          itemsOnPage: perPage,
          cssStyle: "light-theme",
          onPageClick: function(pageNumber) {
            var showFrom = perPage * (pageNumber - 1);
            var showTo = showFrom + perPage;
            items.hide()
                .slice(showFrom, showTo).show();
          }
        });
      });
    }
  }

}(jQuery, Drupal, drupalSettings));
