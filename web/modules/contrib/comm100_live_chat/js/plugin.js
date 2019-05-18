		/*
			* Comm100 Plugin JavaScript Package 2.0 
			* Modify Date: 2014-07-22
			* http://www.comm100.com
		*/
		//register source
			
		
		var register_source = 'plugin.drupal';
		var appId = 'Ng==';

		//register button id
		var register_submit = 'register_submit';
		//login button id
		var login_submit = 'login_submit';
		//save account button id
		var save_account = 'save_account';
		//post form id  link
		var post_form_id = 'comm100-live-chat-settings-form';
		//unlink form
		var post_form_unlink_id = 'comm100-live-chat-settings-form';
		//unlink button id
		var btn_unlink = 'btn_unlink';
		//expire tip message div id
		var comm100livechat_expire = 'comm100livechat_expire';
		//install successful message div id
		var comm100livechat_guide = 'comm100livechat_guide';
		//select code plan and site div id
		var comm100livechat_site_and_plan = 'comm100livechat_site_and_plan';
		//login div id 
		var comm100livechat_login = 'comm100livechat_login';
		//register div id
		var comm100livechat_register = 'comm100livechat_register';
		var register_ip = '';

		//service URL
		var Comm100RouteServiceDomain = "route.comm100.com"; 
		var Comm100RouteServiceDomain1 = "route1.comm100.com";
		//var cpanel_domain = 'host.comm100.com';
		var requestIndex = 0;
		var comm100_script_id = 0;
		var settings = drupalSettings;
		var props = settings.comm100.comm100_admin.livechat_props;

		String.prototype.trim = function()
		{
			return this.replace(/(^[\s]*)|([\s]*$)/g, "");
		}

		function html_encode(html) {
			var div=document.createElement("div");
			var txt=document.createTextNode(html);
			div.appendChild(txt);
			return div.innerHTML;
		}

		if (typeof comm100_script_id == 'undefined')
			comm100_script_id = 0;
			
		function comm100_script_request(STYPE,requestIndex,params, success, error, timeout, errormessage)
		{
			// console.log('comm100_script_request');
			var cpanel_domain = jQuery("#txt_cpanel_domain").val();
			function request() {
				var _id = 'comm100_script_' + comm100_script_id++;
				var _success;
				var _timer_timeout;

				function _append_script(id, src) {
					var scr = document.createElement('script');
					scr.src = src;
					scr.id = '_' + _id;
					scr.type = 'text/javascript';
					document.getElementsByTagName('head')[0].appendChild(scr);
				}
				this.send = function _send (url, success, error) {
					_append_script(_id, url + '&callback=' + _id + '.onresponse');
					_timer_timeout = setTimeout(function() {
						if (error) error(errormessage);
					}, timeout);

					_success = success || function() {};		
				}
				this.onresponse = function _onresponse(response) {
					window[_id] = null;
					var scr = document.getElementById('_' + _id);
					document.getElementsByTagName('head')[0].removeChild(scr);

					clearTimeout(_timer_timeout);

					_success(response);
				}
				window[_id] = this;
			}

			var req = new request();

			var _domain = Comm100RouteServiceDomain;
			if(STYPE==1)
			{
				if(requestIndex==1)
				{
					_domain = Comm100RouteServiceDomain1;
				}
				_domain = _domain + "/routeserver/pluginhandler.ashx";
			}
			if(STYPE==2)
			{
				_domain = cpanel_domain + '/AdminPluginService/livechatplugin.ashx';
			}
			var serviceUrl = "https://"+_domain+params;
			
			if(typeof comm100livechat_session == null) {
				setTimeout(function() {
					req.send(serviceUrl, success, error);
				}, 1000);
			} else {
				req.send(serviceUrl, success, error);
			}
		}

		var comm100_plugin = (function() {
			function _onexception(msg) {
				alert(msg);
			}

			function checkemail(str){
				var Expression=/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/; 
				var objExp=new RegExp(Expression);
				if(objExp.test(str)==true){
					return true;
				}else{
					return false;
				}
			}
			
			function IsMail(mail)
			{ 
			var patrn = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
			if (!patrn.test(mail))
				return false;
			else
				return true;
			}
			
			function _get_timezone() {
				return ((new Date()).getTimezoneOffset() / -60.0).toString();
			}
			
			function _register() {
				//hideErrorMessage();
				//showLoadingBar(register_submit);
				jQuery('#comm100livechat_register .ajax_message').removeClass('message').addClass('wait').html('Please wait&hellip;');
				var edition = 74;
				var source = register_source;
				var name = document.getElementById('register_name').value;
				var email = document.getElementById('register_email').value;
				var password = document.getElementById('register_password').value;
				var phone = document.getElementById('register_phone').value;
				var website = document.getElementById('register_website').value;
				var ip = document.getElementById('register_ip').value;
				var timezone = _get_timezone();
				//var verification_code = document.getElementById('register_verification_code').value;
				var referrer = window.location.href;
						
				if(email=='')
				{
					jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html('Email required.');
					document.getElementById('register_email').focus();
					return false;
				}

				if(!IsMail(email))
				{
					jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html('Incorrect email.');
					document.getElementById('register_email').focus();
					return false;
				}
				
				if(password=='')
				{
					jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html('Password required.');
					document.getElementById('register_password').focus();
					return false;
				}
				
				document.getElementById(register_submit).disabled = true;
				
				name = encodeURIComponent(name);
				email = encodeURIComponent(email);
				password = encodeURIComponent(password);
				phone = encodeURIComponent(phone);
				website = encodeURIComponent(website);
				ip = encodeURIComponent(ip);
				timezone = encodeURIComponent(timezone);
				//verification_code = encodeURIComponent(verification_code);
				var verification_code = '';
				referrer = encodeURIComponent(referrer);
				
				comm100_script_request(1,0,'?action=register&float_button=true&edition=' + edition + '&name=' + name + '&email=' + email +
					'&password=' + password + '&phone=' + phone + '&website=' + website + '&ip=' + ip + '&timezone=' + timezone + '&verificationCode=' + verification_code + '&referrer=' + referrer
					+ '&source=' + source
					, function(response) {
						document.getElementById(register_submit).disabled = false;
						if(response.success) {
							jQuery("#login_email").val(jQuery("#register_email").val());
							jQuery("#login_password").val(jQuery("#register_password").val());
							_sitesNoStep(jQuery("#register_email").val(),jQuery("#register_password").val());
							hideAjaxMessage();
						}
						else {
							jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html(response.error);
							if (error) error('Server error. Please try again later.');//setValidationCodeImage();
							}

					}, function(message) {
						document.getElementById('register_submit').disabled = false;
						jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html(message);
					}, 30 * 1000, 'Unexpected error. Please have a live chat with our support team or email support@comm100.com.');
			}
			
			function _get_plan_type(plan) {
				if (plan.button_type == 2) {
					return 0;  //monitor
				} else if (plan.button_type == 0 && plan.button_float) /*float*/ {
					return 1; //float image
				} else {
					return 2; //others,  need widget
				}
			}
			function _login(success, error) {
				var email = encodeURIComponent(document.getElementById('login_email').value);
				var password = encodeURIComponent(document.getElementById('login_password').value);
				var timezone = encodeURIComponent(_get_timezone());
				var site_id = encodeURIComponent(document.getElementById('txt_site_id').value.trim());
				document.getElementById('txt_email').value = document.getElementById('login_email').value;
				document.getElementById('txt_password').value = document.getElementById('login_password').value;
				
				comm100_script_request(2,0,'?action=login&siteId=' + site_id + '&email=' + email + '&password=' + password
					, function(response) {
						if(response.success) {
							_get_plans(site_id, function(response) {
								var plans = response;
									if (plans.length == 1 && _get_plan_type(plans[0]) == 0) {
										_show_plans(plans);
										if(jQuery("#comm100_sites_select option").length == 1)
										{
											jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('message').addClass('wait').html('Linking up&hellip;');
											onComm100Save();
										}
									}
									else if (plans.length == 1 && jQuery("#comm100_sites_select option").length==1 ) {
									//set plan and go to next step
									jQuery("#txt_plan_id").val(plans[0].id);
									jQuery("#txt_plan_type").val(_get_plan_type(plans[0]));
									jQuery("#edit-codeplan").hide();
									_show_plans(plans);
									//onComm100Save();
								} else {  
									_show_plans(plans);
									
								}
							});
						}
						else {
							error(response.error);
						}
					}, function(message) {
						error(response.message);
					}, 10 * 1000, 'Operation timeout.');
			}
			
			function _login2(success, error) {
				var email = encodeURIComponent(document.getElementById('txt_email').value);
				var password = encodeURIComponent(document.getElementById('txt_password').value);
				var timezone = encodeURIComponent(_get_timezone());
				//var site_id = encodeURIComponent(document.getElementById('txt_site_id').value.trim());
				if(typeof(site_id)=='undefined' || site_id=='' || parseInt(site_id)< 1 )
				{
					return;
				}
				
				comm100_script_request(2,0,'?action=login&siteId=' + site_id + '&email=' + email + '&password=' + password
					, function(response) {
						if(response.success) {
							
						}
						else {
							
						}
					}, function(message) {
						
					}, 10 * 1000, 'Operation timeout.');
			}
			
			function _get_plans(site_id, success, error) {
				comm100_script_request(2,0,'?action=plans&siteId=' + site_id, function(response) {
					if(response.error) {
						if (typeof error != 'undefined')
							error('Comm100 Live Chat is not added to your site yet as you haven\'t linked up any Comm100 account.<br/><a href="admin.php?page=comm100livechat_settings">Link Up your account now</a> and start chatting with your visitors.');
					} else {
						success(response.response);
					}
				});
			}

			function _show_plans(plans) {
				//set step
				setStepShow(3);

				var selPlansObj = jQuery("#comm100_codeplans_select");
				selPlansObj.empty();
				var enode = document.createElement("div"); 

				for (var i= 0, len=plans.length; i<len; i++) {
					var p = plans[i];		
					enode.innerText = p.name;	
					selPlansObj.append("<option id='plan_option_'" +p.id +"' value='"+p.id+"' data-plantype='"+_get_plan_type(p)+"' data-button-image-type='"+p.button_image_type+"' data-button-offline-id='"+p.button_offline_id+"' data-button-offline-url='"+p.button_offline_url+"' data-button-online-id='"+p.button_online_id+"' data-button-online-url='"+p.button_online_url+"' data-button-type='"+p.button_type+"' data-button-text-content='"+p.button_text_content+"' >"+enode.innerHTML+"</option>");  
				}
				
				//init variables
				var planid = "0";
				var plantype = "0";
				var img_type = "0";
				var img_offline_id = "0";
				var img_online_id = "0";
				var img_offline_url = "";
				var img_online_url = "";
				
				selPlansObj.change(function(){
					var plan = jQuery(this).children('option:selected');
					var planid = plan.val();
					var siteid = jQuery("#txt_site_id").val();
					var plantype = plan.attr("data-plantype");
					var img_type = plan.attr("data-button-image-type");
					var img_offline_id = plan.attr("data-button-offline-id");
					var img_online_id = plan.attr("data-button-online-id");
					var img_offline_url = plan.attr("data-button-offline-url");
					var img_online_url = plan.attr("data-button-online-url");
					var button_type = plan.attr("data-button-type");
					var button_text_content = plan.attr("data-button-text-content");
					_on_codeplan_selected(planid,plantype,img_type,img_offline_id,img_online_id,img_offline_url,img_online_url,button_type,button_text_content);
					hideAjaxMessage();
				});
				
				//set default
				var plan = plans[0];	
				var planid = plan.id;
				var plantype = _get_plan_type(plan);
				var img_type = plan.button_image_type;
				var img_offline_id = plan.button_offline_id;
				var img_online_id = plan.button_online_id;
				var img_offline_url = plan.button_offline_url;
				var img_online_url = plan.button_online_url;
				var button_type = plan.button_type;
				var button_text_content = plan.button_text_content;
				_on_codeplan_selected(planid,plantype,img_type,img_offline_id,img_online_id,img_offline_url,img_online_url,button_type,button_text_content);
				
				//one site one plan direct to step 4
				if(plans.length==1 && jQuery("#comm100_sites_select option").length==1)
				{
					if(button_type == 2)
					{
						jQuery("#save_account").hide();
						jQuery("#link_title").hide();
						return;
					}
					else
					{
						jQuery("#link_title").show();
						jQuery("#edit-site").hide();
						jQuery("#edit-codeplan").hide();
						jQuery("#preViewImgBox").attr("position", "relative");
						jQuery("#imgOnline").css("padding-left", "0");
						jQuery("#imgOffline").css("padding-left", "0");
						jQuery("#preViewImgBox").css("border-left", "0");
						jQuery("#preViewImgBox label").css("margin-left", "0");
						jQuery("#preViewImgBox").css("position", "relative");
						jQuery("#preViewImgBox").css("top", "0");
						jQuery("#preViewImgBox").css("left", "0");
						jQuery("#preViewImgs").css("width", "400px");
						jQuery("#preViewImgs span").css("margin-left", "10px");
						jQuery("#preViewTextButton").css("margin-left", "10px");
						jQuery("#lblOnlineText").css("margin-left", "0");
						jQuery("#button_area").removeClass('linkbutton2');
						if( button_type == 0)
						{
							var h = jQuery("#preViewImgBox").height();
							jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", h/2);
						}
						else
						{
							jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", "20px");
						}
						jQuery("#save_account").show();
					}
				}
				else if( jQuery("#comm100_sites_select option").length==1 )
				{
					jQuery("#edit-site").hide();
					jQuery("#link_title").show();
					jQuery("#edit-codeplan").show();
					jQuery("#preViewImgBox").show();
					var h = jQuery("#preViewImgBox").height();
					jQuery("#save_account").css("margin-top", h/2);
					jQuery("#save_account").show();
					
				}
				else
				{
					
					if(button_type == 2)
					{
						jQuery("#preViewImgBox").hide();
						jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", "20px");
					}
					else
					{
						jQuery("#preViewImgBox").show();
						var h = jQuery("#preViewImgBox").height();
						jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", h/2);
					}
					jQuery("#link_title").show();
					jQuery("#edit-site").show();
					jQuery("#edit-codeplan").show();
					jQuery("#save_account").show();
					var h = jQuery("#preViewImgBox").height();
					jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", h/2);
				}
			}
			
			function _on_codeplan_selected(planid,plantype,img_type,img_offline_id,img_online_id,img_offline_url,img_online_url,button_type,button_text_content)
			{
				jQuery("#txt_plan_id").val(planid);
				jQuery("#txt_plan_type").val(plantype);
				if(button_type==0 || button_type==3) //image button or adaptive type
				{
					if(img_online_id == 0 && img_online_url=="")
					{
						jQuery("#preViewImgBox").hide();
						return;
					}
					
					var siteid = jQuery("#txt_site_id").val();
					var main_chatserver_domain = jQuery("#txt_main_chatserver_domain").val();
					var standby_chatserver_domain = jQuery("#txt_standby_chatserver_domain").val();
					var img_online = "https://"+main_chatserver_domain+"/DBResource/DBImage.ashx?imgId="+img_online_id+"&type="+img_type+"&siteId="+siteid;
					var img_offline = "https://"+main_chatserver_domain+"/DBResource/DBImage.ashx?imgId="+img_offline_id+"&type="+img_type+"&siteId="+siteid;
					if(img_type==0)
					{
						img_online = img_online_url;
						img_offline = img_offline_url;
					}
					
					//show preview images
					jQuery("#preViewImgBox").show();
					jQuery("#preViewImgs").show();
					var path = jQuery("#base_url").val();
					jQuery("#imgOnline").src = path + "/sites/all/modules/comm100_live_chat/images/ajax_loader.gif";
					jQuery("#imgOnline").src = path + "/sites/all/modules/comm100_live_chat/images/ajax_loader.gif";
					jQuery("#preViewTextButton").hide();
					jQuery("#lblOnlineText").hide();
					preloadImage(img_online,'imgOnline',imgCallback);
					preloadImage(img_offline, 'imgOffline',imgCallback);
					jQuery("#imgOnline").show();
					jQuery("#imgOffline").attr("src",img_offline);
					jQuery("#imgOffline").show();
					jQuery("#save_account").show();
					var h = jQuery("#preViewImgBox").height();
					jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", h/2);
				}
				else if(button_type==1) //text button
				{
					jQuery("#preViewImgBox").show();
					jQuery("#preViewImgs").hide();
					jQuery("#preViewTextButton").show();
					jQuery("#lblOnlineText").show();
					jQuery("#lblOnlineText a").html(button_text_content);
					var h = jQuery("#preViewImgBox").height();
					jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", "20px");
				}
				else
				{
					jQuery("#preViewImgBox").hide();
					jQuery("#comm100livechat_site_and_plan .ajax_message").css("margin-top", "20px");
				}
			}
			
			function _get_code(site_id, plan_id, callback) {
				comm100_script_request(2,0,'?action=code&siteId=' + site_id + '&planId=' + plan_id, function(response) {
					callback(response.response);
				});
			}
			function _get_editions(callback) {
				comm100_script_request(2,0,'?action=editions', function(response) {
					callback(response.response);
				});
			}

			function _show_sites(sites) {
				//set step    	
				setStepShow(3);
				//_issiteexpire();
				if(sites.length<1)
				{
					jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html('You have not create a site.');
					return;
				}
				if(sites.length>1)
				{
					jQuery("#edit-site").show();
					jQuery("#edit-codeplan").hide();
					jQuery("#save_account").show();
				}
				var selSitesObj = jQuery("#comm100_sites_select");
				selSitesObj.empty();
				var defaultSelectSiteId = 0;
				var haveActiveSite = false;
				var defInactiveSiteId = 0;
				for (var i= 0, len=sites.length; i<len; i++) {
					var s = sites[i];
					if(s.inactive)
					{
						defInactiveSiteId = s.id;
						continue;
					}
					if(defaultSelectSiteId==0)
					{
						defaultSelectSiteId = s.id;
					}
					haveActiveSite = true;
					selSitesObj.append("<option value='"+s.id+"'>"+s.id+"</option>");  
				}
				if(!haveActiveSite)
				{
					jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html('Your Comm100 account is inactive. Please <a href=" http://www.comm100.com/secure/login.aspx" target="_blank" >sign in</a> and activate your account first.');
					return;
				}
				
				selSitesObj.change(function(){
					hideAjaxMessage();
					document.getElementById(save_account).disabled = false;
					var siteid = jQuery(this).children('option:selected').val();
					jQuery("#txt_site_id").val(siteid);
					_login(function () {
					
					}, function (error) {
						jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html(error);
						document.getElementById(save_account).disabled = true;
					})
				});
				
				jQuery("#txt_site_id").val(defaultSelectSiteId);
				_login(function () {
				}, function (error) {
					jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html(error);
				})
				
				if( sites.length == 1)
				{
					jQuery("#edit-site").hide();
				}
				//document.getElementById('num_sites').innerHTML = sites.length;
			}
			
			function _sitesNoStep(email,password)
			{
				document.getElementById('txt_email').value = email;
				document.getElementById('txt_password').value = password;
				comm100_script_request(1,0,'?action=sites&email='+email+'&password='+password+'&timezoneoffset='+(new Date()).getTimezoneOffset(), 
				function (response) {
					document.getElementById(login_submit).disabled = false;
					if (response.success) {
						document.getElementById('txt_cpanel_domain').value = response.cpanel_domain;
						jQuery("#txt_main_chatserver_domain").val(response.main_chatserver_domain);
						jQuery("#txt_standby_chatserver_domain").val(response.standby_chatserver_domain);
						
						var sites = response.response;
						if (sites.length == 0) {
							jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html('There is no site associate this account');
							return;
						}

						document.getElementById('txt_site_id').value = sites[0].id;
						document.getElementById('txt_password').value = password;
						document.getElementById('txt_cpanel_domain').value = response.cpanel_domain;
						// cpanel_domain = response.cpanel_domain;
						document.getElementById('txt_main_chatserver_domain').value = response.main_chatserver_domain;
						document.getElementById('txt_standby_chatserver_domain').value =response.standby_chatserver_domain;
						_show_sites(response.response);
					} else {
						jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html(error);
					}
				});
			}

			function _sites () {
				//set step
				// setStepShow(1);
				jQuery('#comm100livechat_login .ajax_message').removeClass('message').addClass('wait').html('Please wait&hellip;');

				var email = document.getElementById('login_email').value;
				var password = document.getElementById('login_password').value;
				
				if(email=='')
				{
					jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html('Email required.');
					document.getElementById('login_email').focus();
					return false;
				}

				if(!IsMail(email))
				{
					jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html('Incorrect email.');
					document.getElementById('login_email').focus();
					return false;
				}
				
				if(password=='')
				{
					jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html('Password required.');
					document.getElementById('login_password').focus();
					return false;
				}
				
				document.getElementById('login_submit').disabled = true;
				email = document.getElementById('login_email').value;
				password = document.getElementById('login_password').value;
				
				
				comm100_script_request(1,0,'?action=sites&email='+email+'&password='+password+'&timezoneoffset='+(new Date()).getTimezoneOffset(), 
				function (response) {
					document.getElementById('login_submit').disabled = false;
					if (response.success) {
						var sites = response.response;
						if (sites.length == 0) {
							jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html('There is no site associate this account');
							return;
						}

						document.getElementById('txt_site_id').value = sites[0].id;
						document.getElementById('txt_password').value = password;
						document.getElementById('txt_cpanel_domain').value = response.cpanel_domain;
						// cpanel_domain = response.cpanel_domain;
						document.getElementById('txt_main_chatserver_domain').value = response.main_chatserver_domain;
						document.getElementById('txt_standby_chatserver_domain').value = response.standby_chatserver_domain;
						
						//for(var i= 0, len=sites.length; i<len; i++) {
							_show_sites(response.response);
						//}
						
					} else {
						jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html(response.error);
					}
				},
				function(msg){
					comm100_script_request(1,1,'?action=sites&email='+email+'&password='+password+'&timezoneoffset='+(new Date()).getTimezoneOffset(), function(response){
					document.getElementById('login_submit').disabled = false;
					if (response.success) {
						var sites = response.response;
						if (sites.length == 0) {
							jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html('There is no site associate this account');
							return;
						}
						document.getElementById('txt_email').value = email;
						document.getElementById('txt_site_id').value = sites[0].id;
						document.getElementById('txt_password').value = password;
						document.getElementById('txt_cpanel_domain').value = response.cpanel_domain;
						// cpanel_domain = response.cpanel_domain;
						document.getElementById('txt_main_chatserver_domain').value = response.main_chatserver_domain;
						document.getElementById('txt_standby_chatserver_domain').value = response.standby_chatserver_domain;
						_show_sites(response.response);
						
					} else {
						//showErrorMessage(response.error);
						jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html(response.error);
						document.getElementById('login_submit').disabled = false;
					}
					},
					function(msg){
						jQuery('#comm100livechat_login .ajax_message').removeClass('wait').addClass('message').html(msg);
						document.getElementById('login_submit').disabled = false;
					}, 10 * 1000, 'Operation timeout. Pleras try again later.');
				}, 10 * 1000, 'Operation timeout. Pleras try again later.');
				/*}, function(message) {
					document.getElementById('login_submit').disabled = false;
					jQuery('#comm100livechat_register .ajax_message').removeClass('wait').addClass('message').html(response.error);
				});*/
			}
			
			function _issiteexpire () {
				var siteid = jQuery("#txt_site_id").val();
				var cpanel_domain = jQuery("#txt_cpanel_domain").val();
				var _data = '?action=issiteexpire&siteid='+siteid+'&timezoneoffset='+(new Date()).getTimezoneOffset();
				var service = 'https://' + cpanel_domain + '/AdminPluginService/livechatplugin.ashx' + _data;
				jQuery("#"+comm100livechat_expire).hide();
				comm100_script_request(2,0,_data, 
						function (response) {
					if (response.success) {
						var result = response.response;
						if (result == 0) {
							jQuery("#"+comm100livechat_expire).hide();
							return false;
						}
						else
						{
							jQuery("#"+comm100livechat_expire).show();
							return true;
						}
						
					} else {
						//showErrorMessage(res.error);
					}
						});
			}
			
			return {
				register: _register,
				login: _login,
				get_plans: _get_plans,
				get_code: _get_code,
				get_editions: _get_editions,
				sites: _sites,
				issiteexpire: _issiteexpire,
				login2 : _login2
			};
		})();

		function hide_element(id) {
			document.getElementById(id).style.display = 'none';
		}
		function show_element(id, display) {
			document.getElementById(id).style.display = display || '';
		}
		function is_empty(str) {
			return (!str || /^\s*jQuery/.test(str));
		}
		function is_email(str) {
			var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))jQuery/;
			return re.test(str);
		}
		function is_input_empty(input_id) {
			return is_empty(document.getElementById(input_id).value);
		}
		function is_input_email(input_id) {
			return is_email(document.getElementById(input_id).value);
		}

		function setStepShow(step)
		{
			//set step
			jQuery("#txtCurrentStep").val(step);
			//clear error message
			//hideErrorMessage();
			
			/*
				* 1 : login
				* 2 : register
				* 3 : choose site and code plan
				* 4 : success guide
				*  */
			jQuery("#choose_form").show();
			jQuery("#"+comm100livechat_guide).hide();
			jQuery("#"+comm100livechat_expire).hide();
			jQuery("#"+comm100livechat_login).hide();
			jQuery("#"+comm100livechat_site_and_plan).hide();
			jQuery("#"+comm100livechat_register).hide();
			jQuery("#comm100_guide_message").hide();
			if(step == 1)
			{
				jQuery('#comm100livechat_register').hide();
				jQuery('#comm100livechat_login').show();
				jQuery('#comm100livechat_site_and_plan').hide();
				jQuery('#login_email').focus();
				jQuery('#edit-submit').hide();
				jQuery('#edit-submit').disabled = true;
			}
			else if(step == 2)
			{
				//setValidationCodeImage();
				jQuery('#comm100livechat_login').hide();
				jQuery('#comm100livechat_site_and_plan').hide();
				jQuery('#comm100livechat_register').show();
				jQuery('#edit-name').focus();
				jQuery('#edit-submit').attr('value', 'Create account');					
			}
			else if(step == 3)
			{
				jQuery("#console").hide();
				jQuery("#"+comm100livechat_site_and_plan).show();
				jQuery("#link_title").hide();
				jQuery("#edit-site").hide();
				jQuery("#edit-codeplan").hide();
				jQuery("#preViewImgBox").hide();
				jQuery("#ajax_message").show();
				jQuery("#edit-submit").hide();
				jQuery("#save_account").hide(); 
			}
			else if(step == 4)
			{
				setStaticButtonJSCode();
				jQuery("#comm100livechat_guide0").show();
				jQuery("#comm100livechat_guide").show();
				jQuery("#comm100_guide_message").show();
				jQuery("#choose_form").hide();
				jQuery('#edit-submit').hide();
			}
		}

		function hideErrorMessage()
		{
			jQuery("#comm100_error").hide();
			jQuery("#comm100_error_text").html("");
		}

		function showErrorMessage(error_msg)
		{
			//clear load div
			jQuery("#comm100livechat_login .comm100_loading").hide();
			jQuery("#comm100livechat_login .comm100_error").show();
			jQuery("#comm100livechat_login .comm100_error_text").html(error_msg);
		}

		function hideLoadingBar()
		{
			jQuery("#comm100_loading").hide();
		}

		function showLoadingBar(elmentId)
		{
			jQuery("#"+elmentId).attr("disable",true);
			var offset = jQuery("#"+elmentId).offset();
			var x = jQuery('#'+elmentId).offset().top;
			var y = jQuery('#'+elmentId).offset().left;
			jQuery("#comm100_loading").show();
			jQuery("#comm100_loading").attr("style","z-index:100;margin-top:"+x+";margin-left:"+y+";");
		}

		function onComm100Save()
		{
			//get code
			var site_id = jQuery("#txt_site_id").val();
			var plan_id = jQuery("#txt_plan_id").val();
			
			jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('message').addClass('wait').html('Linking up&hellip;');
			
			if(site_id=="" || site_id <1)
			{
				jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html('Please select a site!');
				return;
			}
			if(plan_id=="" || plan_id <1)
			{
				jQuery('#comm100livechat_site_and_plan .ajax_message').removeClass('wait').addClass('message').html('Please select a code plan!');
				return;
			}
			
			//upload link action
			uploadActionLog(1);
		}

		function monitorOnlySave()
		{
			var site_id = jQuery("#txt_site_id").val();
			var plan_id = jQuery("#txt_plan_id").val();	
		}

		function submitComm100Form_Link()
		{
			jQuery("#txt_postdatatype").val("link");
			// jQuery("#"+post_form_id).submit();
			jQuery.ajax({
					type: 'POST',
					dataType: 'json',
					url: settings.comm100.comm100_admin.save_link_url,
					data: {
						site_id: jQuery('#txt_site_id').val(),
						plan_id: jQuery('#txt_plan_id').val(),
						plan_type: jQuery('#txt_plan_type').val(),
						plugin_version: jQuery('#txt_plugin_version').val(),
						email: jQuery('#txt_email').val(),
						cpanel_domain: jQuery('#txt_cpanel_domain').val(),
						main_chatserver_domain: jQuery('#txt_main_chatserver_domain').val(),
						standby_chatserver_domain: jQuery('#txt_standby_chatserver_domain').val()							
					}
				});
				
			setStepShow(4);
		}

		function submitComm100Form_UnLink()
		{
			jQuery("#txt_postdatatype").val("unlink");
			jQuery('#comm100livechat_guide .ajax_message').removeClass('message').addClass('wait').html('Please wait&hellip;');
			//upload unlink action
			uploadActionLog(4);	
		}

		function submitUnlinkForm()
		{
			//jQuery("#"+post_form_unlink_id).submit();
			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: settings.comm100.comm100_admin.save_unlink_url
			});
		}

		function hideAjaxMessage() {
			jQuery('.ajax_message').html('');
		}

		/*
		function setValidationCodeImage()
		{
			var d = new Date();
			var timestamp = d.getMilliseconds();
			jQuery("#register_validation_img").attr("src","");
			jQuery("#register_validation_img").attr("src",'https://hosted.comm100.com/AdminPluginService/(S(' + comm100livechat_session + '))/livechatplugin.ashx?action=verification_code&timestamp='+timestamp);
		}
		*/
		function resetLinkData()
		{
			jQuery("#txt_email").val('');
			jQuery("#txt_password").val('');
			jQuery("#txt_plan_id").val('0');
			jQuery("#txt_plan_type").val('0');
			jQuery("#txt_site_id").val('0');
			jQuery("#txt_actiontype").val('0');
			jQuery("#txt_cpanel_domain").val('');
			jQuery("#txt_main_chatserver_domain").val('');
			jQuery("#txt_standby_chatserver_domain").val('');
		}

		function setStaticButtonJSCode()
		{
			var plantype = jQuery("#txt_plan_type").val();
			var linkedemail = jQuery("#txt_email").val();
			var siteid = jQuery("#txt_site_id").val();
			var planid = jQuery("#txt_plan_id").val();
			var cpanel_domain = jQuery("#txt_cpanel_domain").val();
			var main_chatserver_domain = jQuery("#txt_main_chatserver_domain").val();
			var standby_chatserver_domain = jQuery("#txt_standby_chatserver_domain").val();
			jQuery("#console").hide();
			jQuery("#billinglink").attr('href', "https://"+cpanel_domain+"/Billing/Client/Plan/ChooseLiveChat.aspx?siteid="+siteid);
			jQuery('#getonline').attr('href',"https://"+cpanel_domain+"/livechat/visitormonitor.aspx?siteid="+siteid);
			jQuery('#customizelink').attr('href', "https://"+cpanel_domain+"/LiveChatFunc/CodePlan/ChatButton.aspx?ifEditPlan=true&codePlanId="+planid+"&siteId="+siteid);
			jQuery('#linkaccount strong').text(linkedemail);
			if(plantype==0 || plantype==1)
			{
				jQuery("#comm100_button_code_area").hide();
			}
			else if(plantype==2)
			{
				var code = jQuery("#chat_button_code_template").val();
				
				var re_siteid = new RegExp("{siteid}", "g");
				var re_planid = new RegExp("{codeplanid}", "g");
				var re_main_chatserver_domain = new RegExp("{main_chatserver_domain}", "g");
				var re_standby_chatserver_domain = new RegExp("{standby_chatserver_domain}", "g");
				code = code.replace(re_siteid,siteid);
				code = code.replace(re_planid,planid);
				code = code.replace(re_main_chatserver_domain,main_chatserver_domain);
				code = code.replace(re_standby_chatserver_domain,standby_chatserver_domain);
				jQuery("#chat_button_code").val(code);
				jQuery("#comm100_guide_message").hide();
				jQuery("#comm100_button_code_area").show();
			}	
		}

		function uploadActionLog(actionType)
		{
			/*
			1 : Link
			2 : Install
			3 : Uninstall
			4 : Unlink
			*/
			
			var siteId = jQuery("#txt_site_id").val();
			var planId = jQuery("#txt_plan_id").val();
			var domain =  encodeURIComponent(window.location.host);
			var linkedEmail = encodeURIComponent(jQuery("#txt_email").val());
			var pluginVersionNo = encodeURIComponent(jQuery("#txt_plugin_version").val());
			var checkPluginURL = encodeURIComponent(jQuery("#txt_plugin_check_url").val());
			var _data = "?action=uploadactionlog&siteId="+siteId+"&actionType="+actionType+"&planId="+planId+"&appId="+appId+"&domain="+domain+"&linkedEmail="+linkedEmail+"&pluginVersionNo="+pluginVersionNo+"&checkPluginURL="+checkPluginURL+"&timezoneoffset="+(new Date()).getMilliseconds();;
			comm100_script_request(2,0,_data, function(response) {
						goAfterLog(actionType);
					}, function(message) {
						goAfterLog(actionType);
			});
			
		}

		function goAfterLog(actionType)
		{
			if(actionType==1)
			{
				submitComm100Form_Link();
			}
			else if(actionType==4)
			{
				resetLinkData();
				submitUnlinkForm();
				setStepShow(1);
				hideAjaxMessage();
			}
		}

		var preloadImage= function (url, elementId,callback) {
			var img = new Image();
				img.src = url;

			if (img.complete) {
				return callback(url,elementId);
			};

			img.onload = function () {
			callback(url,elementId);
			img.onload = img.onerror = null;
			};

			img.onerror = function () {};
		};

		function imgCallback(url,elementId)
		{
			jQuery("#"+elementId).attr("src", url);
		}

		function checkExpire()
		{
			comm100_plugin.issiteexpire();
		}

		function getIp()
		{
			/* $.getJSON("http://www.telize.com/jsonip?callback=?",
				function(json) {
					register_ip = json.ip;
				}
			); */
		}

		function initDatas() {
			jQuery('#txt_site_id').val(props.site_id);
			jQuery('#txt_plan_id').val(props.plan_id);
			jQuery('#txt_plan_type').val(props.plan_type);
			jQuery('#txt_plugin_version').val(props.plugin_version);
			jQuery('#txt_email').val(props.email);
			jQuery('#txt_cpanel_domain').val(props.cpanel_domain);
			jQuery('#txt_main_chatserver_domain').val(props.main_chatserver_domain);
			jQuery('#txt_standby_chatserver_domain').val(props.standby_chatserver_domain);
		}

		jQuery('#login_submit').on('click', function () {
			comm100_plugin.sites();
		});
		
		jQuery('#register_submit').on('click', function () {
			comm100_plugin.register();
		});
		
			
		jQuery('#save_account').on('click', function () {
			onComm100Save();
		});

		jQuery('#btn_unlink').on('click', function () {
			submitComm100Form_UnLink();
			hideAjaxMessage();
		});
		
		//jQuery('#ac_create_now').on('click', function () {
		//	setStepShow(2);
		//	jQuery('#comm100livechat_register .ajax_message').html('');
		//});

		jQuery('#link_up_to_myaccount').one('click', function () {
			setStepShow(1);
			hideAjaxMessage();
		});

		setStepShow(0);

		if (props.site_id <= 0 || props.site_id === '' || props.site_id === null) {
			setStepShow(1);
		}else if(props.site_id <= 0 || props.site_id === '' || props.site_id === null) {
			initDatas();
			setStepShow(3);
		}else{
			initDatas();
			setStepShow(4);
			jQuery('#comm100_guide_message').hide();
		}

		//setValidationCodeImage();
		//get domain
		jQuery("#register_website").val(document.domain); 
		
		setStaticButtonJSCode();
		jQuery('#edit-actions').remove();

		checkExpire();
		
		//if linked then login to comm100
		comm100_plugin.login2();


		jQuery(document).keyup(function(event){
				if(jQuery("#txt_site_id").val()>0)
				{
					jQuery("#txtCurrentStep").val("4");
				}
				if(event.keyCode ==13){
					var step = jQuery("#txtCurrentStep").val();
					if(step=="1")//login
					{
						jQuery("#login_submit").trigger("click");
					}
					else if(step=="2") //reg
					{
					jQuery("#register_submit").trigger("click");
					}
					else if(step=="3") //site and code plan
					{
					jQuery("#save_account").trigger("click");
					}
					return false;
				}
		});