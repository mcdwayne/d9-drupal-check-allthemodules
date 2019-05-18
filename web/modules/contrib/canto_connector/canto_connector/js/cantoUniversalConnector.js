/*!
    Canto Universal Connector 1.0.0
    Dependencies on jQuery.
*/
(function ($, document, window, Drupal) {
    var cantoUC,
    pluginName = "cantoUC",
    redirectUri = "",
    tokenInfo = {},
    env = "flightbycanto.com",  //flightbycanto.com/staging.cantoflight.com/canto.com/canto.global
    appId = "f5ecd6095ebb469691b7398e4945eb44",
    callback,
    currentCantoTagID;

    cantoUC = $.fn[pluginName] = $[pluginName] = function (options, callback) {
        /*! options.env:   flightbycanto.com/staging.cantoflight.com/canto.com/canto.global
        */
        settings(options);
        callback = callback;
        loadCantoUCResource();
        createIframe();
        addEventListener();
        //initCantoTag();
      
        window.onmessage=function(e){
            var data = event.data;
            if(data && data.type == "getTokenInfo"){
                var receiver = document.getElementById('cantoUCFrame').contentWindow;
                receiver.postMessage(tokenInfo, '*');
            } else if(data && data.type == "cantoLogout"){
            	if(tokenInfo.accessToken){
            	 $.ajax({
                     url: Drupal.url('canto_connector/delete_access_token'),
                     type: 'POST',
                     data: { 'accessToken': tokenInfo.accessToken, 'env': env
                     	
                     },
                     dataType: 'json',
                   });
            	}
                //clear token and close the frame.
                tokenInfo = {};
                $(".canto-uc-iframe-close-btn").trigger("click");
    
            } else if(data && data.type == "cantoInsertImage"){
                $(".canto-uc-iframe-close-btn").trigger("click");
                // insertImageToCantoTag(cantoURL);
                callback(currentCantoTagID, data.assetList);

            } else {
            	$.ajax({
                    url: Drupal.url('canto_connector/save_access_token'),
                    type: 'POST',
                    data: { 'accessToken': data.accessToken, 'tokenType': data.tokenType, 'subdomain':data.refreshToken},
                    dataType: 'json',
                  });
                tokenInfo = data;
                var cantoContentPage = "https://s3-us-west-2.amazonaws.com/static.dmc/universal/cantoContent.html";
                //var cantoContentPage = "./cantoAssets/cantoContent.html";
                $("#cantoUCFrame").attr("src", cantoContentPage);
            }
    
        };
    };
    function settings(options){
        var envObj = {
            "flightbycanto.com":"f5ecd6095ebb469691b7398e4945eb44",
            "staging.cantoflight.com":"f18c8f3b79644b168cad5609ff802085",
            "canto.com":"a9dc81b1bf9d492f8ee3838302d266b2",
            "canto.global":"f87b44d366464dfdb4867ab361683c96"
        };
        env = options.env;
        appId = envObj[env];
        if(options.tenants && options.tenants.length>1 && options.accessToken && options.accessToken.length>1)
        {
        console.log("get token info from Drupal DB");
        tokenInfo={accessToken:options.accessToken,tokenType:options.tokenType,refreshToken:options.tenants};
        }
    }
    function loadCantoUCResource() {
        // dynamicLoadJs("./cantoAssets/main.js");
        dynamicLoadCss("./cantoAssets/base.css");
    }
    function addEventListener() {

        $(document).on('click',".canto-uc-iframe-close-btn", function(e){
            $("#cantoUCPanel").addClass("hidden");
            $("#cantoUCFrame").attr("src", "");
        })
       .on('click', ".img-box", function(e){
            //currentCantoTagID = $(e.target).closest("canto").attr("id");
            $("#cantoUCPanel").removeClass("hidden");
            loadIframeContent();
        });
    }
    /*--------------------------load iframe content---------------------------------------*/
    function initCantoTag(){
        var body = $("body");
        var cantoTag = body.find("canto");
        var imageHtml = '<button class="canto-pickup-img-btn">+ Insert Files from Canto</button>';
    
        cantoTag.append(imageHtml);
    }

    /*--------------------------load iframe content---------------------------------------*/
    function loadIframeContent() {

        var cantoLoginPage = "https://oauth." + env + "/oauth/api/oauth2/universal/authorize?response_type=code&app_id=" + appId + "&redirect_uri=http://loacalhost:3000&state=abcd";
        //environment.
        /* If you want to deploy this to env, please select one and delete others, include above.*/
        // var cantoLoginPage = "https://oauth.flightbycanto.com/oauth/api/oauth2/universal/authorize?response_type=code&app_id=f5ecd6095ebb469691b7398e4945eb44&redirect_uri=http://loacalhost:3000&state=abcd";
        // var cantoLoginPage = "https://oauth.staging.cantoflight.com/oauth/api/oauth2/universal/authorize?response_type=code&app_id=f18c8f3b79644b168cad5609ff802085&redirect_uri=http://loacalhost:3000&state=abcd";
        // var cantoLoginPage = "https://oauth.canto.com/oauth/api/oauth2/universal/authorize?response_type=code&app_id=a9dc81b1bf9d492f8ee3838302d266b2&redirect_uri=http://loacalhost:3000&state=abcd";
        // var cantoLoginPage = "https://oauth.canto.global/oauth/api/oauth2/universal/authorize?response_type=code&app_id=f87b44d366464dfdb4867ab361683c96&redirect_uri=http://loacalhost:3000&state=abcd";
        
         var cantoContentPage = "https://s3-us-west-2.amazonaws.com/static.dmc/universal/cantoContent.html";
       // var cantoContentPage = "./cantoAssets/cantoContent.html";
        // $("#cantoUCFrame").attr("src", cantoContentPage);
        if(tokenInfo.accessToken){
            $("#cantoUCFrame").attr("src", cantoContentPage);
        } else {
            $("#cantoUCFrame").attr("src", cantoLoginPage);
        }
    }
    /*--------------------------add iframe---------------------------------------*/
    function createIframe() {
        var body = $("body");
        var iframeHtml = '<div class="canto-uc-frame hidden" id="cantoUCPanel">';
        iframeHtml += '<div class="header">';
        iframeHtml += '<div class="title">Canto Content</div>';
        iframeHtml += '<div class="close-btn icon-s-closeicon-16px canto-uc-iframe-close-btn"></div>';
        iframeHtml += '</div>';
        iframeHtml += '<iframe id="cantoUCFrame" class="canto-uc-subiframe" src=""></iframe>';
        iframeHtml += '</div>';
        
        body.append(iframeHtml);
    }
    /*--------------------------load file---------------------------------------*/
    function dynamicLoadJs(url, callback) {
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        if(typeof(callback)=='function'){
            script.onload = script.onreadystatechange = function () {
                if (!this.readyState || this.readyState === "loaded" || this.readyState === "complete"){
                    callback();
                    script.onload = script.onreadystatechange = null;
                }
            };
        }
        head.appendChild(script);
    }
    function dynamicLoadCss(url) {
        var head = document.getElementsByTagName('head')[0];
        var link = document.createElement('link');
        link.type='text/css';
        link.rel = 'stylesheet';
        link.href = url;
        head.appendChild(link);
    }


}(jQuery, document, window, Drupal));