
function WWAF(wrapper_id, settings, overrides) {

    this.id = wrapper_id;
    this.debug = settings.debug? true : false;

    if (this.debug)
        console.log("[WWAF]: new WWAF() constructor invoked");

    this.config = settings;

    this.geolocating = false;
    this.defaults = {};
    this.map = null;
    this.mc = null;
    this.info = null;
    this.ui = {};
    this.markers = [];
    this.geocoder = null;
    this.data = {};

    // custom api
    this.api = {
        infoMarkup: null,
        locationMarkup: null,
        trackEvent: null,
        resultsAfter: null,
        renderArrayAlter: null
    };
};

WWAF.prototype.setApi = function(method, fun) {

    if (this.debug) console.log("[WWAF]: Overriding api method `" +method +"`");

    if (Object.keys(this.api).indexOf(method) != -1)
        this.api[method] = fun;
};

WWAF.prototype.alert = function (msg, type, callback) {
    if (window['swal'] !== undefined) {
        var opts = {
            title: type,
            text: msg,
            type: type
        };
        swal(opts, callback);
    }
    else {
        alert(msg);
    }
};

WWAF.prototype.gup = function (name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
};

WWAF.prototype.haversine = function (center, place) {
    var R     = 6371;   //Earth radius in km
    var lat1  = place.lat();
    var lng1  = place.lng();
    var lat2  = center.lat();
    var lng2  = center.lng();
    var dLat  = (lat2-lat1) * Math.PI / 180;
    var dLng  = (lng2-lng1) * Math.PI / 180;
    var lat1R = lat1 * Math.PI / 180;
    var lat2R = lat2 * Math.PI / 180;
    var a     = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.sin(dLng/2) * Math.sin(dLng/2) * Math.cos(lat1R) * Math.cos(lat2R);
    var c     = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;  // distance from center to place.
};

WWAF.prototype.toggleLoading = function () {
    $sf = this;
    $sf.ui.loading.toggleClass("on");
    if ($sf.debug) console.log("[WWAF]: Toggled loading");
};

WWAF.prototype.clearMarkers = function () {
    $sf = this;
    while($sf.markers.length>0) {
        var m = $sf.markers.pop();
        m.setMap(null);
    }

    $sf.ui.results.empty();

    if ($sf.cur_marker)
        $sf.cur_marker.setMap(null);

    if($sf.config.clusters && $sf.mc)
        $sf.mc.clearMarkers();
};

WWAF.prototype.defatulLocationMarkup = function (store) {
    var h = '';
    var _title = store.getTitle() || '';
    var _address = '';
    var _phone = ''
    var _email = '';
    var _emailIcon = '';
    var _website = '';
    var _websiteIcon = '';
    var _description = '';
    var _location = '<span class="map-action icon"></span>';

    if (store.hasOwnProperty('getFormattedAddress') && store.getFormattedAddress()) {
        _address = '<address class="info">'+ store.getFormattedAddress() +'</address>'
    }

    if (store.hasOwnProperty('getPhone') && store.getPhone()) {
        _phone = '<div class="phone info">'+ store.getPhone() +'</div>';
    }

    if (store.hasOwnProperty('getEmail') && store.getEmail()) {
        _email     = '<a class="email info" href=\"mailto:\"' + store.getEmail() + '\">'+ store.getEmail() +'</a>';
        _emailIcon = '<a class="email-icon icon" href=\"mailto:\"' + store.getEmail() + '\"></a>';
    }

    if (store.hasOwnProperty('getWebsite') && store.getWebsite('uri')) {
        var _websiteTitle = store.getWebsite('title') || store.getWebsite('uri');
        _website     = '<a class="website info" href=\"' + store.getWebsite('uri') + '\">'+ _websiteTitle +'</a>';
        _websiteIcon = '<a class="website-icon icon" href=\"' + store.getWebsite('uri') + '\"></a>';
    }

    if (store.hasOwnProperty('getDescription') && store.getDescription()) {
        _description = '<div class="description info">'+store.getDescription()+'</div>';
    }

    h += '<h4>' + _title + '<div class="icons-wrapper">' + _websiteIcon + _location + _emailIcon +'</div></h4>' +
         _address + _phone + _email + _website + _description;
    return h;
};

WWAF.prototype.locationMarkup = function (store, index) {
    $sf = this;
    var types = typeof store.getType == "function" ? store.getType().join(' ') : '';
    var markup = '<div class="location-wrapper '+types+'" data-marker-index="'+index+'">';

    if (typeof $sf.api.locationMarkup == "function") {
        // Override by API
        markup += $sf.api.locationMarkup(store, index);
    }
    else {
        // Default
        markup += $sf.defatulLocationMarkup(store, index);
    }

    return markup + '</div>';
};

WWAF.prototype.defaultInfoMarkup = function (store) {
    $sf = this;
    var h = '';
    if (typeof store.getImage == "function" || typeof store.getLogo == "function") {
        h+= '<div class="mediaWrap">';
        if (typeof store.getImage == "function")
            h+= '<div class="imgWrap"> <img src="'+store.getImage() +'" alt="" /> </div>';
        if (typeof store.getLogo == "function")
            h+= '<div class="logoWrap"> <img src="'+store.getLogo() +'" alt="" /> </div>';
        h+= '</div>';
    }
    else {
        var noMediaClass = typeof store.getImage != "function" && typeof store.getLogo != "function"? 'no-media' : 'has-media';
    }


    h += '<div class="body '+noMediaClass+'"> \
            <h3 class="title">'+store.getTitle()+'</h3> \
            <div class="info address"> \
                <strong>' +Drupal.t('Address') +'</strong> \
                <span class="value">'+store.getFormattedAddress()+'</span> \
            </div>';

    if (typeof store.getPhone == "function" && store.getPhone()) {
        h += '<div class="info phone"> <strong>' +Drupal.t('Telephone') +'</strong> <span class="value">'+store.getPhone()+'</span> </div>';
    }

    if (typeof store.getWebsite == "function" && store.getWebsite()) {
        h += '<div class="info website"> <strong>' +Drupal.t('Website') +'</strong> <a href="'+store.getWebsite()+'" class="value" target="_blank">'+store.getWebsite()+'</a> </div>';
    }

    h += '</div>';

    return h;
};

WWAF.prototype.infoMarkup = function (store) {
    $sf = this;
    var markup = '';

    if (typeof $sf.api.infoMarkup == "function") {
        // Override by API
        markup = $sf.api.infoMarkup(store);
    }
    else {
        // Default
        markup = $sf.defaultInfoMarkup(store);
    }

    return markup;
};

WWAF.prototype.resetInfo = function () {
    $sf = this;
    $sf.ui.info.find(".content").empty();
};

WWAF.prototype.closeInfo = function () {
    $sf = this;
    $sf.ui.wrap.removeClass("info-on");
};

WWAF.prototype.openInfo = function (store) {
    $sf = this;
    $info = $sf.ui.info;
    if ($sf.debug) console.log("[WWAF]: openInfo()");

    $sf.closeInfo();
    $sf.resetInfo();

    setTimeout(function () {
        $sf.ui.info.find(".content").html(  $sf.infoMarkup(store)  );
        $sf.ui.wrap.addClass("info-on");

    }, 200);
};

WWAF.prototype.mapUnreduce = function (store, map) {

    var obj = store;
    obj.getId = function () {
        return obj[ map.nid ];
    };
    obj.getTitle = function () {
        return obj[ map.title ];
    };
    obj.getDescription = function () {
        return obj [ map.description ] || '';
    };
    obj.getGeometry = function () {
        return obj[ map.geometry ];
    };
    obj.getAddress = function () {
        var _addr = obj[ map.address['_k'] ];
        var _map = map.address['_v'];
        return {
            country: _addr[ _map.country_code ],
            administrative: _addr[ _map.administrative_area ],
            locality: _addr[ _map.locality ],
            dependent: _addr[ _map.dependent_locality ],
            zipCode: _addr[ _map.postal_code ],
            sortingCode: _addr[ _map.sorting_code ],
            addressLine1: _addr[ _map.address_line1 ],
            addressLine2: _addr[ _map.address_line2 ]
        };
    };
    obj.getFormattedAddress = function () {
        var _a = this.getAddress();
        var addr = _a.addressLine1 + (_a.addressLine2? (' '+_a.addressLine2):'');
        if (_a.locality) addr += ' - ' +_a.locality;
        if (_a.administrative) addr += ' ('+_a.administrative+')';
        if (_a.zipCode) addr += ', '+_a.zipCode;
        addr+= ', '+_a.country;
        return addr;
    };

    var keys = Object.keys(map);
    for (var k = 0; k < keys.length; k ++) {
        var key = keys[k];
        if (key.indexOf("field_") == -1)
            continue;

        (function(key, map) {
            var newkey = ('get_'+key.substr(6)).replace(/_\w/g, function (m) { return m[1].toUpperCase(); });
            obj[newkey] = function(arg = 'value') {
                var _val = obj[ map[key] ];
                if (_val != null && typeof _val['indexOf'] == "function") {
                    var arr = [];
                    for (var i = 0; i < _val.length; i++)
                        arr.push(_val[i].value);
                    return arr;
                }
                else if (_val != null && Object.keys(_val).length > 0) {
                    return arg == 'object' ? _val : _val[arg];
                }
                else {
                    return null;
                }
            }
        }(key, map));
    }

    return obj;
};

WWAF.prototype.markerClick = function (click, noMarker) {
    $sf = this;

    if (noMarker) {

    }
    else {
        // unsetting previos marker:
        if ($sf.cur_marker != null && $sf.config.use_active) {
            $sf.cur_marker.setIcon($sf.defaults.markers.normal);
            $sf.cur_marker.setZIndex(5);
        }

        if ($sf.cur_marker != click) {
            // initiating info for clicked one:
            $sf.cur_marker = click;

            if ($sf.config.use_active)
                click.setIcon($sf.defaults.markers.active);

            if ($sf.config.location_info)
                $sf.openInfo(click.store);

            click.setZIndex(100);
        }
        else {
            $sf.closeInfo();
            $sf.cur_marker.setZIndex(5);
            $sf.cur_marker = null;
        }

        //track events
        if ($sf.config.track.enabled && typeof $sf.api.trackEvent == "function")
            $sf.api.trackEvent($sf.config.track.name, 'store detail', this.store.getTitle(), 1, true);
    }

};

WWAF.prototype.renderMarkers = function (render) {
    $sf = this;
    $sf.currentRender = render;
    $sf.clearMarkers();
    $sf.ui.wrap.removeClass("initial-on");
    if (render.length > 0) {
        $sf.ui.search.addClass("has-results")
    }
    else {
        $sf.ui.search.removeClass("has-results")
    }

    $sf.bounds = $sf.map.getBounds();

    if ($sf.config.clusters) {
        if ($sf.debug) console.log("[WWAF]: Clusters enabled - making new instance");
        $sf.mc = null;
        $sf.mc = new MarkerClusterer($sf.map, [], $sf.markerClusterOptions);
    }

    for (var i = 0; i < render.length; i++) {
        var store = render[i],
            _marker;

        if (store._position) {
            _marker = new google.maps.Marker({
                position: store._position,
                icon: $sf.defaults.markers.normal,
                map: $sf.map,
                title: store.getTitle(),
                zIndex: 5,
                store: store
            });


            // adding click listener:
            google.maps.event.addListener(_marker, 'click', function() {
                $sf.markerClick(this);
            });

            // saving into array:
            $sf.markers.push(_marker);

            // extending bounds of the map
            if (!$sf.config.clusters && !$sf.bounds.contains(store._position))
                $sf.bounds.extend(store._position);

            // or adding it to clusterer
            if ($sf.config.clusters)
                $sf.mc.addMarker(_marker);
        }

        // Rendering info into list
        if ($sf.config.location_markup)
            $sf.ui.results.append(  $sf.locationMarkup(store, i)  );
    }

    if (!$sf.config.clusters)
        $sf.map.fitBounds($sf.bounds);

    $sf.toggleLoading();

    // if there's markers markup - link it to the map
    if ($sf.config.location_markup) {
        $sf.ui.results.find(".location-wrapper").each(function () {
            var $me = jQuery(this),
                index = parseInt($me.attr("data-marker-index")),
                link = $me.find(".map-action")
            ;

            link.click(function () {
                if (link.hasClass("no-marker")) {
                    var embStoreIndex = Number($me.attr("data-marker-index"));
                    var embStore = $sf.currentRender[embStoreIndex];
                    $sf.openInfo(embStore);
                }
                else {
                    $sf.map.panTo( $sf.markers[index].getPosition() );
                    $sf.map.setZoom(15);
                    new google.maps.event.trigger( $sf.markers[index], 'click' );
                }
            });
        });
    }

};

WWAF.prototype.readData = function (data, radius) {
    var to_render = [];

    for (var i = 0; i < data.records.length; i++) {
        var store = $sf.mapUnreduce(data.records[i], $sf.map_reduce),  // Un-reducing the JSON object
            geo = store.getGeometry(),
            coords = geo.lat && geo.lng ? new google.maps.LatLng(geo.lat, geo.lng) : null,
            distance = coords? $sf.haversine($sf.center, coords) : 0    // no distance calculated with null coords
        ;

        if (radius && distance > radius)
            continue;

        store._range = distance;
        store._position = coords;
        to_render.push(store);
    }

    to_render.sort(function (a, b) {
        if (a.getType == "function") {
            var arr = ['distributor', 'eshop', 'shop'];
            var a1 = a.getType()[0], b1 = b.getType()[0];
            var x = arr.indexOf(a1), y = arr.indexOf(b1);
            var ret = x < y ? -1 : ( x > y ? 1 : 0 );
            if (ret !== 0) return ret;
        }

        var x1 = a._range, y1 = b._range;
        return x1 < y1 ? -1 : (x1 > y1 ? 1 : 0);
    });

    if ($sf.api.renderArrayAlter) {
        to_render = $sf.api.renderArrayAlter(to_render);
    }

    if ($sf.debug) console.log('[WWAF]: Sorted by distance from center');

    $sf.renderMarkers(to_render);

    if ($sf.api.resultsAfter) {
        var marketHtml = $sf.api.resultsAfter(data);
        $sf.ui.afterResults.html( marketHtml );
    }
};

WWAF.prototype.getAddressComponent = function (arr, type) {

    for(var k in arr) {
        var comp = arr[k];
        if (comp.types[0] == type)
            return comp;
    }
    return null;
};

WWAF.prototype.fetchData = function (r0, radius) {
    $sf = this;

    var locality_name, locality = $sf.getAddressComponent(r0.address_components, 'locality');
    if (locality) {
        locality_name = locality.short_name;
    }
    else {
        locality = $sf.getAddressComponent(r0.address_components, 'country');
        locality_name = locality.long_name;
    }

    //track events
    if ($sf.config.track.enabled && typeof $sf.api.trackEvent == "function")
        $sf.api.trackEvent($sf.config.track.name, 'Search', locality_name, 1, true);

    var country = $sf.getAddressComponent(r0.address_components, 'country');
    if (!$sf.data[country.short_name]) {
        if ($sf.debug)
            console.log("[WWAF]: Looking for data of ", country.short_name);

        var URL = location.protocol +'//' +location.host + $sf.config.feed +'?country=' +country.short_name;
        if ($sf.debug) console.log("[WWAF]: Fetching data -> GET " + URL);
        jQuery.ajax({
            url: URL,
            type: 'GET',
            async: true,
            success: function (data) {
                if (data.status == "OK") {
                    if ($sf.map_reduce === undefined && data.map)
                        $sf.map_reduce = data.map;
                    $sf.data[country.short_name] = data;
                    $sf.readData(data, radius);
                }
                else {
                    $sf.alert(data, 'error')
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $sf.alert(textStatus, 'error');
            }
        });
    }
    else {
        $sf.readData($sf.data[country.short_name], radius);
    }
};

WWAF.prototype.distillGeoArray = function (geoArr) {
    return geoArr[0];
    // or more complex logic here that
    // tells which result to use as the
    // right one:
    // ...
};

WWAF.prototype.search = function (value, radius) {
    $sf = this;
    radius = radius !== undefined ? radius : 30;
    if ($sf.map == null) {
        $sf.search_force = true;
        $sf.init_map($sf.mapOptions,   true   );      // forcing to start
        return;
    }

    $sf.closeInfo();
    $sf.toggleLoading();
    $sf.clearMarkers();
    $sf.mc = null;

    var geoRequest = { address: value };
    $sf.geocoder.geocode(geoRequest, function (arr, status) {
        switch(status) {
            case "OK":
                var r0 = $sf.distillGeoArray(arr);
                $sf.center = r0.geometry.location;
                $sf.bounds = r0.geometry.viewport;
                $sf.ui.search.find("input").val(r0.formatted_address);
                $sf.map.setCenter($sf.center);

                if (!$sf.config.clusters) {
                    switch (radius) {
                        case '10': $sf.map.setZoom(14); break;
                        case '20': $sf.map.setZoom(13); break;
                        case '30': $sf.map.setZoom(12); break;
                        case '40': $sf.map.setZoom(12); break;
                        case '50': $sf.map.setZoom(11); break;
                        default:   $sf.map.fitBounds($sf.bounds); break;
                    }
                }
                else {
                    switch (radius) {
                        case '10': $sf.map.setZoom(12); break;
                        case '20': $sf.map.setZoom(11); break;
                        case '30': $sf.map.setZoom(10); break;
                        case '40': $sf.map.setZoom(9);  break;
                        case '50': $sf.map.setZoom(8);  break;
                        default:   $sf.map.fitBounds($sf.bounds); break;
                    }
                    if ($sf.debug) console.log("[WWAF]: Map zoom: " + $sf.map.getZoom());
                }

                $sf.fetchData(r0, radius);
                break;

            case "ZERO_RESULTS":
                $sf.toggleLoading();
                $sf.alert("Location not found for `"+value+"`");
                break;

            default:
                $sf.toggleLoading();
                $sf.alert("Geocoder error: "+status, "error");
                break;
        }
    });
};

WWAF.prototype.centerMe = function () {

    navigator.geolocation.getCurrentPosition(function (position) {
        $sf.position = position;
        $sf.center = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
        $sf.zoom = 10;

        $sf.map.panTo($sf.center);
        $sf.map.setZoom(10);


        if ($sf.me) {
            $sf.me.setPosition($sf.center);
        }
        else {
            var me = new google.maps.Marker({
                position: $sf.center,
                map: $sf.map,
                title: 'Your location'
            });

            $sf.me = me;
        }
    });
};

WWAF.prototype.init_ui = function () {
    $sf = this;

    if ($sf.ui === undefined) {
        $sf.alert("Undefined UI property inside WWAF!", "error");
        return;
    }

    if ($sf.ui_init_done) {
        if ($sf.search_force) {
            $sf.search_force = false;
            $sf.ui.form.trigger('submit');
        }
        return; // skip RE-initting the UI if it's already done
    }

    $sf.ui.search.find(".toggle").click(function () {
        $sf.ui.mwrap.toggleClass("search-on");
    });
    
    var $select = $sf.ui.search.find("select");
    $select.change(function () {
        var wrap = $select.parent(),
            caption = wrap.find(".caption .value"),
            text = $select.find("option:selected").text();

        caption.html(text);

        if ($sf.ui.search.find("input").val().trim() != '')
            $sf.ui.form.submit();
    });
    
    var $countries = $sf.ui.search.find('input[type="checkbox"]');
    $countries.change(function () {
      if ($(this).is(':checked')) {
        $select.attr("disabled", "disabled");
        $select.parent().addClass("disabled");
      }
      else {
        $select.removeAttr("disabled");
        $select.parent().removeClass("disabled");
      }
    });

    $sf.ui.search.find('form').on('submit', function (e) {
      if (e.preventDefault)
          e.preventDefault();

      $sf.ui.form.find("input").blur();

      var value  = $sf.ui.search.find("input").val(),
          is_country = $sf.ui.search.find('input[name="is_country"]').is(':checked'),
          radius = !is_country ? $sf.ui.search.find('select').val() : false
      ;

      $sf.search(value, radius);  //<-- Searching invoke
      return false;
    });
    $sf.ui.search.find('input[type="text"]').on('keydown', function (event) {
      if (event.which == 13 || event.keyCode == 13) {
        event.preventDefault();

        $sf.ui.form.find("input").blur();

        var value  = $sf.ui.search.find("input").val(),
            is_country = $sf.ui.search.find('input[name="is_country"]').is(':checked'),
            radius = !is_country ? $sf.ui.search.find('select').val() : false
        ;

        $sf.search(value, radius);  //<-- Searching invoke
        return false;
      }
      return true;
    });

    // init search if there's Query params!
    if ($sf.gup('search') && $sf.gup('search') != '') {

      $sf.ui.search.find('input').val($sf.gup('search'));
      $sf.ui.search.find('select option[value="'+$sf.gup('radius')+'"]').attr("selected", "selected");
      if ($sf.gup('is_country') == '1' ) {
        $countries.attr("checked", "checked");
      }

      $sf.ui.form.find("input").blur();

      var value  = $sf.ui.search.find('input[name="search"]').val(),
          is_country = $sf.ui.search.find('input[name="is_country"]').is(':checked'),
          radius = !is_country ? $sf.ui.search.find('select').val() : false
      ;

      $sf.search(value, radius);  //<-- Searching invoke
    }

    $sf.ui.search.fadeIn();
    $sf.ui.gps.fadeIn();

    $sf.ui.info.find(".btnClose").click(function () {
        if ($sf.cur_marker)
            $sf.cur_marker.setIcon($sf.defaults.markers.normal);
        $sf.closeInfo();
    });

    $sf.ui_init_done = true;
};

WWAF.prototype.init_map = function (options, force) {
    $sf = this;

    if (!$sf.config.hide_map || force) {
        if ($sf.debug) console.log("[WWAF]: init_map()");
        var mapLoaded = false;
        $sf.map = new google.maps.Map($sf.ui.map, options);
        google.maps.event.addListener($sf.map, 'idle', function() {
            if (!mapLoaded) {
                mapLoaded = true;

                if ($sf.geolocation && $sf.position) {
                    $sf.me = new google.maps.Marker({
                        icon: $sf.defaults.markers.position,
                        position: $sf.center,
                        map: $sf.map,
                        title: 'Your location'
                    });
                }

                if ($sf.config.clusters) {
                    if ($sf.debug) console.log("[WWAF]: Configuring clusters");
                    $sf.markerClusterOptions = {
                         maxZoom: 12
                        ,gridSize: 70
                        ,styles: [
                            {
                                url: $sf.config.images.cl_small,
                                width: 64, height: 64,
                                anchor: [0,0],
                                textColor: 'white',
                                fontFamily: '"Open Sans", Arial, sans-serif',
                                fontWeight: 'light',
                                textSize: 14
                            },
                            {
                                url: $sf.config.images.cl_medium,
                                width: 128, height: 128,
                                anchor: [0,0],
                                textColor: 'white',
                                fontFamily: '"Open Sans", Arial, sans-serif',
                                fontWeight: 'light',
                                textSize: 20
                            },
                            {
                                url: $sf.config.images.cl_large,
                                width: 256, height: 256,
                                anchor: [0,0],
                                textColor: 'white',
                                fontFamily: '"Open Sans", Arial, sans-serif',
                                fontWeight: 'light',
                                textSize: 26
                            }
                        ]
                    };
                }

                $sf.init_ui();
            }
        });

        // google.maps.event.addListener($sf.map, 'zoom_changed', function () {
        //     // TODO: map zoom changed
        // })
    }
    else {
        if ($sf.debug) console.log("[WWAF]: init_map() - SKIPPED");
        $sf.init_ui();
        return;
    }
};

WWAF.prototype.init = function () {
    $sf = this;

    if ($sf.debug) console.log("[WWAF]: Googlemaps JS API Loaded -> init()");

    $sf.defaults.markers = {};

    $sf.defaults.markers.normal = {
        url: $sf.config.images.normal
    };

    if ($sf.config.use_active) {
        $sf.defaults.markers.active = {
            url: $sf.config.images.active
        };
    }

    $sf.geocoder = new google.maps.Geocoder();

    var mapOptions = {
         center: new google.maps.LatLng(0.0, 0.0)
        ,zoom: 2
        ,mapTypeId: google.maps.MapTypeId.ROADMAP
        ,mapTypeControl: false
        ,maxZoom: 16
        ,minZoom: 1
        ,streetViewControl: false
        ,rotateControl: false
        ,panControl: false
        ,draggable : true
        ,mapTypeControl: false
        ,scrollwheel: false
        ,zoomControlOptions: {
            style: google.maps.ZoomControlStyle.SMALL,
            position: google.maps.ControlPosition.LEFT_BOTTOM
        }
    };

    if ($sf.config.snazzy_style && $sf.config.snazzy_style.trim() != '')
        mapOptions.styles = "JSON" in window ? JSON.parse($sf.config.snazzy_style) : $sf.$.parseJSON($sf.config.snazzy_style);

    if (location.protocol == "https:" && "geolocation" in navigator) {
        $sf.geolocation = true;
        navigator.geolocation.getCurrentPosition(function (position) {

            $sf.position = position;
            $sf.center = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
            $sf.zoom = 10;
            mapOptions.center = $sf.center;
            mapOptions.zoom   = $sf.zoom;

            $sf.mapOptions = mapOptions;    // save for future usage;
            $sf.init_map(mapOptions);

        }, function (error) {
            $sf.mapOptions = mapOptions;    // save for future usage;
            switch(error.code) {
                case 1:
                    //$sf.alert("Positioning denied by device", "error", function(){$sf.init_map(mapOptions)});
                    console.error("Geolocation: Positioning denied");
                    $sf.init_map(mapOptions);
                    break;

                case 2:
                    //$sf.alert("Position unavailable", "warning", function(){$sf.init_map(mapOptions)});
                    console.error("Geolocation: Position unavailable");
                    $sf.init_map(mapOptions);
                    break;

                case 3:
                    //$sf.alert("Positioning timeout", "warning", function(){$sf.init_map(mapOptions)});
                    console.error("Geolocation: Positioning timeout");
                    $sf.init_map(mapOptions);
                    break;
            }
        });
    }
    else {
        $sf.mapOptions = mapOptions;    // save for future usage;
        $sf.init_map(mapOptions);
    }
};
