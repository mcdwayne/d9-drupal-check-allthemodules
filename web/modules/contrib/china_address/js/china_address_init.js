(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Rewrite autocomplete inputs to pass the language of the node currently being
   * edited in the path.
   */
  Drupal.behaviors.china_address = {
    attach : function (context, settings) {
      Drupal.china_address.initLocation({sheng_val:settings.china_address.sheng_val, shi_val:settings.china_address.shi_val, xian_val:settings.china_address.xian_val, xiang_val:settings.china_address.xiang_val});    
    }
  };

  Drupal.china_address = {};

  /**
   * Makes elements outside the overlay unreachable via the tab key.
   *
   * @param context
   *   The part of the DOM that should have its tabindexes changed. Defaults to
   *   the entire page.
   */
  Drupal.china_address.initLocation = function (option) {
    option = jQuery.extend({
      sheng:"sheng",	//province dom ID
      shi:"shi",			//city dom ID
      xian:"xian",		//country dom ID
      xiang:"xiang",	//street dom ID
      sheng_val:"",		//default province
      shi_val:"",			//default country
      xian_val:"",		//default city
      xiang_val:""		//default street
    },option||{});

    if(option.sheng_val == ""){
      option.sheng_val == "-1";
    }
      
    var gpm = new GlobalProvincesModule;

    gpm.def_province = ["---", -1];

    gpm.initProvince(document.getElementById(option.sheng));

    gpm.initCity1(document.getElementById(option.shi), option.sheng_val);

    gpm.initCity2(document.getElementById(option.xian), option.sheng_val, option.shi_val);

    gpm.initCity3(document.getElementById(option.xiang), option.sheng_val, option.shi_val, option.xian_val);

    gpm.selectProvincesItem(document.getElementById(option.sheng), option.sheng_val);

    gpm.selectCity2Item(document.getElementById(option.xian), option.xian_val);

    gpm.selectCity1Item(document.getElementById(option.shi), option.shi_val);	
    
    if(document.getElementById(option.xiang).options.length > 1){
      gpm.selectCity2Item(document.getElementById(option.xiang), option.xiang_val);
      document.getElementById(option.xiang).style.display ="inline";
      document.getElementById(option.xiang).style.display = "inline";
    }

    var onchgProv = function(){	
      gpm.initCity1(document.getElementById(option.shi), gpm.getSelValue(document.getElementById(option.sheng)));
      gpm.initCity2(document.getElementById(option.xian), '', '');		/* clear city2 select options*/
      gpm.initCity3(document.getElementById(option.xiang), '', '', '');
    }

    var onchgCity1 = function(){
      gpm.initCity2(document.getElementById(option.xian), gpm.getSelValue(document.getElementById(option.sheng)), gpm.getSelValue(document.getElementById(option.shi)));
      gpm.initCity3(document.getElementById(option.xiang), '', '', '');
    }

    var onchgStreet1 = function(){		
      gpm.initCity3(document.getElementById(option.xiang), gpm.getSelValue(document.getElementById(option.sheng)), gpm.getSelValue(document.getElementById(option.shi)), gpm.getSelValue(document.getElementById(option.xian)));
    }

    
    $("#"+option.sheng).change(onchgProv);
    $("#"+option.shi).change(onchgCity1);
    $("#"+option.xian).change(onchgStreet1);
  };

})(jQuery, Drupal, drupalSettings);
