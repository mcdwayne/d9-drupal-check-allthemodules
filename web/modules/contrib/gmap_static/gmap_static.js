(function ($, Drupal) {

  Drupal.behaviors.gmap_static = {
    /**
     * function add styles to status message window
     * @param context
     * @param settings
     */
    attach: function (context, settings) {
      $(document).ready(function () {
        var view_mode = settings.gmap_view_mode;
        $('iframe').once().each(function (number) {
          var iframesrc = $(this).attr('src');
          if (is_google_map_frame(iframesrc)) {
            create_static_map($(this), 'googlemap' + number, view_mode);
          }
        });
      });

      function popup_map(popup_class) {
        $('body').prepend($('<div/>', {'class': 'gmap_static_bigmap',}));
        $('div.gmap_static_bigmap').once().prepend($('<iframe/>', {
          'class': 'gmap_static_iframe_fullscreen',
          'src': $('iframe.' + popup_class.id).attr('src'),
        }));
        $('div.gmap_static_bigmap').append($('<div/>', {
          'class': 'gmap_static_collapse',
          click: function () {
            $('.gmap_static_bigmap').remove();
          },
        }));
      }

      function change_condition(replaceclass) {
        $('img.' + replaceclass.id).hide();
        $('div#' + replaceclass.id).hide();
        $('iframe.' + replaceclass.id).show();
        $('iframe.' + replaceclass.id).attr('src', $('iframe.' + replaceclass.id).attr('src'));
      }

      function in_new_window(replaceclass) {
        var window_map = window.open();
        var html = $('iframe.' + replaceclass.id).clone().wrap("<iframe/>").parent().html();
        html = html.replace('display: none;', 'display: block;');
        $(window_map.document.body).html(html);
      }

      function map_iframe_source_to_params(src) {
        var attributes = new Array();
        var keys_and_values = new Array();
        var param = new Array();
        var map_type = src.substring(src.lastIndexOf('/'), src.indexOf('?'));
        if (map_type == '/embed') {
          return pb_coordinates(src);
        }
        attributes = (src.substr(0)).split('&');
        for (var j = 1; j < attributes.length; j++) {
          values = attributes[j].split('=');
          param[values[0]] = values[1];
        }
        if (param['q']) {
          param['center'] = param['q'];
          delete param['q'];
        }
        if (map_type == '/place') {
          param['markers'] = param['center'];
        }
        delete param['key'];
        return param;
      }

      function add_map_image(param, height, width, map_id) {
        var map_source = "https://maps.googleapis.com/maps/api/staticmap?";
        for (var key in param) {
          map_source += key + '=' + param[key] + "&";
        }
        map_source += 'size=' + width + 'x' + height + '&';
        map_source += 'scale=2';
        map_source += '&key=' + settings.gmap_static.apiKey;
        $('div.' + map_id).once('add_map_image').append($('<img/>', {
          'class': map_id,
          'src': map_source,
          'width': width,
          'height': height,
        }));
      }

      function is_google_map_frame(iframesrc) {
        var google_maps_link = 'google.com/maps/embed';
        if (iframesrc.indexOf(google_maps_link) != -1) {
          return true;
        }
        else {
          return false;
        }
      }

      function create_static_map($iframe_map, map_id, view_mode) {
        var height = $iframe_map.attr('height');
        var width = $iframe_map.attr('width');
        var param = map_iframe_source_to_params($iframe_map.attr('src'));
        $iframe_map.addClass(map_id);
        $iframe_map.wrap($('<div/>', {'class': 'gmap_static_map_block ' + map_id}));
        add_map_image(param, height, width, map_id);
        if(view_mode != 'none'){
          $iframe_map.after($('<div/>', {
            'class': 'gmap_static_extend',
            'id': map_id,
            click: function () {
              eval(view_mode + '(' + map_id + ')');
            },
          }).once('add_map_image'));
        }
        $iframe_map.hide();
      }

      function pb_coordinates(src) {
        var param = new Array();
        var coordinates = new Array();
        var maptype, coordinate_index, zoom;
        for (var i = 1; i <= 3; i++) {
          coordinate_index = src.indexOf('!' + i + 'd') + 3;
          coordinates[i] = src.substring(coordinate_index, src.indexOf('!', coordinate_index));
        }
        maptype = src.substr(src.indexOf('!5e') + 3, 1);
        zoom = coordinates[1];
        zoom = (zoom / 4.355) / 1.645;
        zoom = Math.log(zoom) / Math.log(2);
        marker = src.indexOf('!2s') + 3;
        param['zoom'] = 24 - ~~zoom;
        param['markers'] = src.substring(marker, src.indexOf('!', marker));
        param['center'] = coordinates[3] + ',' + coordinates[2];
        param['maptype'] = (maptype == 1) ? 'satellite' : 'roadmap';
        return param;
      }
    }
  };
})(jQuery, Drupal);
