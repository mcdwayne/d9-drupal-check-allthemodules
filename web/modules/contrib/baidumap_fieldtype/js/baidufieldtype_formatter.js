
    jQuery(document).ready(function(){
		// 百度地图API功能
		var map = new BMap.Map("l-map");
		try{
			var pp_obj = jQuery.parseJSON(drupalSettings.baidumap.location);
			var pp = new BMap.Point(pp_obj.lng,pp_obj.lat);
			map.centerAndZoom(pp, 18);
			var marker = new BMap.Marker(pp);
			var address = drupalSettings.baidumap.address;
			var phone = drupalSettings.baidumap.phone;
			var profile = drupalSettings.baidumap.profile;
		    var content = '<div class="baidumap-marker">' +
	            Drupal.t('地址') + Drupal.t('：') + address +'<br/>' +
	            Drupal.t('电话') + Drupal.t('：') + phone +'<br/>' + 
	            Drupal.t('简介') + Drupal.t('：') + profile +
	          '</div>';
			    //创建检索信息窗口对象
		    var searchInfoWindow = null;
			searchInfoWindow = new BMapLib.SearchInfoWindow(map, content, {
					title  : drupalSettings.baidumap.title,      //标题
					width  : drupalSettings.baidumap.width,             //宽度
					height : drupalSettings.baidumap.height,              //高度
					panel  : "panel",         //检索结果面板
					enableAutoPan : true,     //自动平移
					searchTypes   :[
						BMAPLIB_TAB_SEARCH,   //周边检索
						BMAPLIB_TAB_TO_HERE,  //到这里去
						BMAPLIB_TAB_FROM_HERE //从这里出发
					]
				});
			map.addOverlay(marker);    //添加标注
			searchInfoWindow.open(marker);
		}catch(e){
			return false;
		}
    })