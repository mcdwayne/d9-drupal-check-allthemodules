
    jQuery(document).ready(function(){
		// 百度地图API功能
		function G(id) {
			return document.getElementById(id);
		}

		var map = new BMap.Map("l-map");
		if('undefined' === jQuery('.baidumap-location') || jQuery('.baidumap-location').val() == ''){
			map.centerAndZoom("北京",12);  // 初始化地图,设置城市和地图级别。
		}
		var ac = new BMap.Autocomplete(    //建立一个自动完成的对象
			{"input" : "suggestId"
			,"location" : map
		});


		var myValue;
		ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
		var _value = e.item.value;
			myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
			G("searchResultPanel").innerHTML ="onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue;
			
			setPlace();
		});

		function setPlace(){
			map.clearOverlays();    //清除地图上所有覆盖物
			function myFun(){
				var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
				//console.log(pp);
				map.centerAndZoom(pp, 18);
				var marker = new BMap.Marker(pp);
				//var address = drupalSettings.baidumap.address;
				//var phone = drupalSettings.baidumap.phone;
				//var profile = drupalSettings.baidumap.profile;

				var address = jQuery('.baidumap-address').val();
				var phone = jQuery('.baidumap-phone').val();
				var profile = jQuery('.baidumap-profile').val();

			    var content = '<div class="baidumap-marker">' +
	                '地址：'+ address +'<br/>\
	                电话：'+ phone +'<br/>\
	                简介：' + profile +
	              '</div>';
				    //创建检索信息窗口对象
			    var searchInfoWindow = null;
				searchInfoWindow = new BMapLib.SearchInfoWindow(map, content, {
						title  : "公司地址",      //标题
						width  : 290,             //宽度
						height : 105,              //高度
						panel  : "panel",         //检索结果面板
						enableAutoPan : true,     //自动平移
						searchTypes   :[
							BMAPLIB_TAB_SEARCH,   //周边检索
							BMAPLIB_TAB_TO_HERE,  //到这里去
							BMAPLIB_TAB_FROM_HERE //从这里出发
						]
					});
				searchInfoWindow.open(marker);
				map.addOverlay(marker);    //添加标注

				jQuery('.baidumap-location').val(JSON.stringify(pp));
			}
			var local = new BMap.LocalSearch(map, { //智能搜索
			  onSearchComplete: myFun
			});
			local.search(myValue);
		}

		if('undefined' !== jQuery('.baidumap-location') && jQuery('.baidumap-location').val() !== ''){
			var pp_obj = jQuery.parseJSON(jQuery('.baidumap-location').val());
			var pp = new BMap.Point(pp_obj.lng,pp_obj.lat);
			map.centerAndZoom(pp, 18);
			var marker = new BMap.Marker(pp);
			var address = drupalSettings.baidumap.address;
			var phone = drupalSettings.baidumap.phone;
			var profile = drupalSettings.baidumap.profile;
		    var content = '<div class="baidumap-marker">' +
                '地址：'+ address +'<br/>\
                电话：'+ phone +'<br/>\
                简介：' + profile +
              '</div>';
			    //创建检索信息窗口对象
		    var searchInfoWindow = null;
			searchInfoWindow = new BMapLib.SearchInfoWindow(map, content, {
					title  : "公司地址",      //标题
					width  : 290,             //宽度
					height : 105,              //高度
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
		}
    })