if(!drupalSettings.comm100.is_admin_page 
	&& drupalSettings.comm100.site_id != '' 
	&& drupalSettings.comm100.site_id != '0' 
	&& drupalSettings.comm100.plan_id != '' 
	&& drupalSettings.comm100.plan_id != '0'
	&& drupalSettings.comm100.plan_type != '2') {

	var Comm100API = Comm100API || new Object;
	Comm100API.chat_buttons = Comm100API.chat_buttons || [];
	var comm100_chatButton = new Object;
	comm100_chatButton.code_plan = drupalSettings.comm100.plan_id;
	comm100_chatButton.div_id = 'comm100-button-' + drupalSettings.comm100.plan_id;
	Comm100API.chat_buttons.push(comm100_chatButton);
	Comm100API.site_id = drupalSettings.comm100.site_id;
	Comm100API.main_code_plan = drupalSettings.comm100.plan_id;

	var comm100_lc = document.createElement('script');
	comm100_lc.type = 'text/javascript';
	comm100_lc.async = true;
	comm100_lc.src = 'https://'+ drupalSettings.comm100.main_chatserver_domain + '/livechat.ashx?siteId=' + Comm100API.site_id;
	var comm100_s = document.getElementsByTagName('script')[0];
	comm100_s.parentNode.insertBefore(comm100_lc, comm100_s);

	setTimeout(function() {
			if (!Comm100API.loaded) {
				var lc = document.createElement('script');
				lc.type = 'text/javascript';
				lc.async = true;
				lc.src = 'https://'+drupalSettings.comm100.standby_chatserver_domain+'/livechat.ashx?siteId=' + Comm100API.site_id;
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(lc, s);
			}
		}, 5000)
	//create comm100 livechat div
	jQuery('<div id="comm100-button-' + drupalSettings.comm100.plan_id + '"></div>').insertAfter('body');
}
