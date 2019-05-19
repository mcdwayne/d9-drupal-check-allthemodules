//
//  File:       wwaf.main.js
//  Project:    marcato-corporate
//  Date:       2017-11-21
//  Developer:  Egor Guriyanov
//  Copyright:  2017 Egor Guriyanov. All rights reserved.
//


var WWAF_INSTANCE = new WWAF('#wwaf', drupalSettings.wwaf);

;(function($, $wwaf, window, undefined) {
    
    $(document).ready(function () {
        
        // init UI variables:
        //-------------------
        var superWrap = $($wwaf.id),
            wrap      = superWrap.find(".wwaf-wrapper"),
            mwrap     = wrap.find(".map-wrapper"),
            map       = document.getElementById("gmap_placeholder"),
            searchBox = wrap.find(".search-box"),
            form      = searchBox.find("form"),
            gpsBox    = wrap.find(".gps-box"),
            infoBox   = wrap.find(".info-box"),
            loading   = wrap.find(".loading"),
            results   = wrap.find(".results-wrapper"),
            after     = wrap.find(".after-wrapper")
        ;
        
        $wwaf.ui = {
            wrap: wrap,
            mwrap: mwrap,
            map: map,
            search: searchBox,
            form: form,
            gps: gpsBox,
            info: infoBox,
            loading: loading,
            results: results,
            afterResults: after
        };
        
        
        // Booting the google maps:
        //---------------------------
        var key = $wwaf.config.api_key;
        var version = $wwaf.config.api_version || '3.29';
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://maps.googleapis.com/maps/api/js?v='+version+'&key='+key+'&callback=WWAF_INSTANCE.init';
        document.body.appendChild(script);

    });
    
}(jQuery, WWAF_INSTANCE, window));


