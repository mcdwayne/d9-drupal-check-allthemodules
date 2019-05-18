(function ($) {
  Drupal.behaviors.moduleeuconsent = {
    attach: function (context, settings) {
      $(document).scroll(function () {
        var scroll_len = drupalSettings.pop_up_scroll;
        var scrollBottom = $(window).scrollTop() + $(window).height();
        scrollBottom > scroll_len ? $('.cc-window').fadeOut() : $('.cc-window').fadeIn();
      });
      var msg = drupalSettings.pop_up_msg,
          pp_href = drupalSettings.privacy_policy_link,
          gtm_container = drupalSettings.gtm_container,
          needs_cookie = drupalSettings.cookie_activate,
          cookie_time = drupalSettings.cookie_duration,
          show_popup_us = drupalSettings.show_popup_us,
          button_color = drupalSettings.button_color,
          background_color = drupalSettings.background_color,
          link_text = drupalSettings.link_text,
          pop_up_position = drupalSettings.pop_up_position,
          gtm_dl_event = drupalSettings.gtm_dl_event;

      var re = new RegExp("/bot|uptimerobot|google|baidu|bing|msn|duckduckbot|teoma|slurp|googlebot\/|Googlebot-Mobile|Googlebot-Image|Google favicon|Mediapartners-Google|bingbot|slurp|java|wget|curl|Commons-HttpClient|Python-urllib|libwww|httpunit|nutch|phpcrawl|msnbot|jyxobot|FAST-WebCrawler|FAST Enterprise Crawler|biglotron|teoma|convera|seekbot|gigablast|exabot|ngbot|ia_archiver|GingerCrawler|webmon |httrack|webcrawler|grub.org|UsineNouvelleCrawler|antibot|netresearchserver|speedy|fluffy|bibnum.bnf|findlink|msrbot|panscient|yacybot|AISearchBot|IOI|ips-agent|tagoobot|MJ12bot|dotbot|woriobot|yanga|buzzbot|mlbot|yandexbot|purebot|Linguee Bot|Voyager|CyberPatrol|voilabot|baiduspider|citeseerxbot|spbot|twengabot|postrank|turnitinbot|scribdbot|page2rss|sitebot|linkdex|Adidxbot|blekkobot|ezooms|dotbot|Mail.RU_Bot|discobot|heritrix|findthatfile|europarchive.org|NerdByNature.Bot|sistrix crawler|ahrefsbot|Aboundex|domaincrawler|wbsearchbot|summify|ccbot|edisterbot|seznambot|ec2linkfinder|gslfbot|aihitbot|intelium_bot|facebookexternalhit|yeti|RetrevoPageAnalyzer|lb-spider|sogou|lssbot|careerbot|wotbox|wocbot|ichiro|DuckDuckBot|lssrocketcrawler|drupact|webcompanycrawler|acoonbot|openindexspider|gnam gnam spider|web-archive-net.com.bot|backlinkcrawler|coccoc|integromedb|content crawler spider|toplistbot|seokicks-robot|it2media-domain-crawler|ip-web-crawler.com|siteexplorer.info|elisabot|proximic|changedetection|blexbot|arabot|WeSEE:Search|niki-bot|CrystalSemanticsBot|rogerbot|360Spider|psbot|InterfaxScanBot|Lipperhey SEO Service|CC Metadata Scaper|g00g1e.net|GrapeshotCrawler|urlappendbot|brainobot|fr-crawler|binlar|SimpleCrawler|Livelapbot|Twitterbot|cXensebot|smtbot|bnf.fr_bot|A6-Indexer|ADmantX|Facebot|Twitterbot|OrangeBot|memorybot|AdvBot|MegaIndex|SemanticScholarBot|ltx71|nerdybot|xovibot|BUbiNG|Qwantify|archive.org_bot|Applebot|TweetmemeBot|crawler4j|findxbot|SemrushBot|yoozBot|lipperhey|y!j-asr|Domain Re-Animator Bot|AddThis|yandex/i", 'i');
      if (re.test(navigator.userAgent)) {
          console.log('the user agent is a crawler!');
      } else {
          $.ajax({
            url: "/ajax/continent", success: function (result) {
              if (result['c_code'] !== 'NA' || show_popup_us === 0) {
                window.cookieconsent.initialise({
                  palette: {
                    popup: {
                      background: background_color,
                    },
                    button: {
                      background: button_color,
                    }
                  },
                  position: pop_up_position,
                  content: {
                    message: msg,
                    dismiss: 'Close',
                    link: link_text,
                    href: pp_href
                  }
                });
              }
              if ((result['c_code'] === 'NA' || result['c_code'] === 'undefined') && needs_cookie === 1) {
                Cookies.set('isNA', 'true', { expires: parseInt(cookie_time) });
              }
              window.dataLayer = (Object.keys(window).indexOf('dataLayer') > -1) ? window.dataLayer : [];
              window.dataLayer.push({
                'gdpr': result['c_code']
              });
              if (typeof(gtm_dl_event) !== 'undefined' || gtm_dl_event !== "") {
                window.dataLayer.push({'event': gtm_dl_event});
              }
              (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
              new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
              j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
              'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
              })(window,document,'script','dataLayer',gtm_container);
            }
        });
      }
    }
  };
}(jQuery));
