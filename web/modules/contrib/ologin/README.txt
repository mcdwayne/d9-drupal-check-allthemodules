
-- SUMMARY --

oLogin is an All-in-one thrid-party login solution, site builders and developers can integrate social media account login ability to their drupal sites.

By default, oLogin integrate WeChat(WeiXin) QRCode login ability to Drupal, by add other add-ons, users can use other social media accounts to login Drupal.


-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/895232 for further information.


-- CONFIGURATION --

* Configure oLogin settings in Configurations » System » oLogin:

  - Wechat Callback URL

    Callback URL for Wechat authentication, use http://yourdomain.com/ologin/weixin (yourdomain.com must be the same one as you set in Wechat open platform)

  - Wechat AppKey & AppSecret

    AppID and AppKey info of your Wechat web app, you can apply from Wechat open platform https://open.weixin.qq.com
    
-- USAGE --

* Print a link with URL of 'ologin/weixin' or visit 'ologin/weixin' directly as anonymous user, scan the QRCode with your Wechat APP to login.
