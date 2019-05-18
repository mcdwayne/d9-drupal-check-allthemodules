window._bd_share_config = {};
window._bd_share_config.common = {};
window._bd_share_config.share = {};
window._bd_share_config.image = {};
window._bd_share_config.selectShare = {};
if(drupalSettings.baidushare.bdText != ''){
	window._bd_share_config.common.bdText = drupalSettings.baidushare.bdText;
}
if(drupalSettings.baidushare.bdUrl != ''){
	window._bd_share_config.common.bdUrl = drupalSettings.baidushare.bdUrl;
}
if(drupalSettings.baidushare.bdPic != ''){
	window._bd_share_config.common.bdPic = drupalSettings.baidushare.bdPic;
}
if(drupalSettings.baidushare.bdSign != ''){
	window._bd_share_config.common.bdSign = drupalSettings.baidushare.bdSign;
}
if(drupalSettings.baidushare.bdMini != ''){
	window._bd_share_config.common.bdMini = drupalSettings.baidushare.bdMini;
}
if(drupalSettings.baidushare.bdMiniList != ''){
	window._bd_share_config.common.bdMiniList = drupalSettings.baidushare.bdMiniList;
}
if(drupalSettings.baidushare.onBeforeClick != ''){
	window._bd_share_config.common.onBeforeClick = eval('onBeforeClick = ' + drupalSettings.baidushare.onBeforeClick);
}
if(drupalSettings.baidushare.onAfterClick != ''){
	window._bd_share_config.common.onAfterClick = eval('onAfterClick = ' + drupalSettings.baidushare.onAfterClick);
}
if(drupalSettings.baidushare.bdPopupOffsetLeft != ''){
	window._bd_share_config.common.bdPopupOffsetLeft = drupalSettings.baidushare.bdPopupOffsetLeft;
}
if(drupalSettings.baidushare.bdPopupOffsetTop != ''){
	window._bd_share_config.common.bdPopupOffsetTop = drupalSettings.baidushare.bdPopupOffsetTop;
}


if(drupalSettings.baidushare.sharetag != ''){
	window._bd_share_config.share.tag = drupalSettings.baidushare.sharetag;
}
if(drupalSettings.baidushare.bdSize != ''){
	window._bd_share_config.share.bdSize = drupalSettings.baidushare.bdSize;
}
if(drupalSettings.baidushare.bdCustomStyle != ''){
	window._bd_share_config.share.bdCustomStyle = drupalSettings.baidushare.bdCustomStyle;
}

if(drupalSettings.baidushare.show_slide == 1){
	window._bd_share_config.slide = {};
	if(drupalSettings.baidushare.bdImg != ''){
		window._bd_share_config.slide.bdImg = drupalSettings.baidushare.bdImg;
	}
	if(drupalSettings.baidushare.bdPos != ''){
		window._bd_share_config.slide.bdPos = drupalSettings.baidushare.bdPos;
	}
	if(drupalSettings.baidushare.bdTop != ''){
		window._bd_share_config.slide.bdTop = drupalSettings.baidushare.bdTop;
	}
}


if(drupalSettings.baidushare.bdImg != ''){
	window._bd_share_config.image.tag = drupalSettings.baidushare.tag;
}
if(drupalSettings.baidushare.viewType != ''){
	window._bd_share_config.image.viewType = drupalSettings.baidushare.viewType;
}
if(drupalSettings.baidushare.viewPos != ''){
	window._bd_share_config.image.viewPos = drupalSettings.baidushare.viewPos;
}
if(drupalSettings.baidushare.viewColor != ''){
	window._bd_share_config.image.viewColor = drupalSettings.baidushare.viewColor;
}
if(drupalSettings.baidushare.viewSize != ''){
	window._bd_share_config.image.viewSize = drupalSettings.baidushare.viewSize;
}
if(drupalSettings.baidushare.viewList != ''){
	window._bd_share_config.image.viewList = drupalSettings.baidushare.viewList;
}


if(drupalSettings.baidushare.bdselectMiniList != ''){
	window._bd_share_config.selectShare.bdselectMiniList = drupalSettings.baidushare.bdselectMiniList;
}
if(drupalSettings.baidushare.bdContainerClass != ''){
	window._bd_share_config.selectShare.bdContainerClass = drupalSettings.baidushare.bdContainerClass;
}

window._bd_share_config
	with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?cdnversion='+~(-new Date()/36e5)];