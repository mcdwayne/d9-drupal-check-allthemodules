(function () {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
})();

function tco(f) {
    /**
     Takes `f` function and returns wrapper in return, that may be
     used for tail recursive algorithms. Note that returned funciton
     is not side effect free and should not be called from anywhere
     else during tail recursion. In other words if
     `var f = tco(function foo() { ... bar() ... })`, then `bar`
     should never call `f`. It is ok though for `bar` to call `tco(foo)`
     instead.

     ## Examples
     var sum = tco(function(x, y) {
    return y > 0 ? sum(x + 1, y - 1) :
           y < 0 ? sum(x - 1, y + 1) :
           x
  })
     sum(20, 100000) // => 100020
     **/

    var value, active = false, accumulated = []
    return function accumulator() {
        // Every time accumulator is called, given set of parameters
        // are accumulated.
        accumulated.push(arguments);
        // If accumulator is inactive (is not in the process of
        // tail recursion) activate and start accumulating parameters.
        if (!active) {
            active = true;
            // If wrapped `f` performs tail call, then new set of parameters will
            // be accumulated causing new iteration in the loop. If `f` does not
            // performs tail call then accumulation is finished and `value` will
            // be returned.
            while (accumulated.length) value = f.apply(this, accumulated.shift());
            active = false;
            return value;
        }
    }
}
//-----------------------------------------------------------------------------
// Date object extensions
// ----------------------------------------------------------------------------
Date.prototype.getTwelveHours = function() {
    hours = this.getHours();
    if (hours == 0) {
        return 12;
    }
    else {
        return hours <= 12 ? hours : hours-12
    }
}

Date.prototype.getTwoDigitMonth = function() {
    return (this.getMonth() < 9) ? '0' + (this.getMonth()+1) : (this.getMonth()+1);
}

Date.prototype.getTwoDigitDate = function() {
    return (this.getDate() < 10) ? '0' + this.getDate() : this.getDate();
}

Date.prototype.getTwoDigitTwelveHour = function() {
    return (this.getTwelveHours() < 10) ? '0' + this.getTwelveHours() : this.getTwelveHours();
}

Date.prototype.getTwoDigitHour = function() {
    return (this.getHours() < 10) ? '0' + this.getHours() : this.getHours();
}

Date.prototype.getTwoDigitMinute = function() {
    return (this.getMinutes() < 10) ? '0' + this.getMinutes() : this.getMinutes();
}

Date.prototype.getTwoDigitSecond = function() {
    return (this.getSeconds() < 10) ? '0' + this.getSeconds() : this.getSeconds();
}

Date.prototype.getHourMinute = function() {
    return this.getTwoDigitHour() + ':' + this.getTwoDigitMinute();
}

Date.prototype.getHourMinuteSecond = function() {
    return this.getTwoDigitHour() + ':' + this.getTwoDigitMinute() + ':' + this.getTwoDigitSecond();
}

Date.prototype.strftime = function(format) {
    var fields = {
        c: this.toString(),
        d: this.getTwoDigitDate(),
        H: this.getTwoDigitHour(),
        I: this.getTwoDigitTwelveHour(),
        m: this.getTwoDigitMonth(),
        M: this.getTwoDigitMinute(),
        p: (this.getHours() >= 12) ? 'PM' : 'AM',
        S: this.getTwoDigitSecond(),
        w: '0' + this.getDay(),
        x: this.toLocaleDateString(),
        X: this.toLocaleTimeString(),
        y: ('' + this.getFullYear()).substr(2, 4),
        Y: '' + this.getFullYear(),
        '%' : '%'
    };
    var result = '', i = 0;
    while (i < format.length) {
        if (format.charAt(i) === '%') {
            result = result + fields[format.charAt(i + 1)];
            ++i;
        }
        else {
            result = result + format.charAt(i);
        }
        ++i;
    }
    return result;
}
Date.prototype.timesince = function(until){
    var until = until || new Date();
    var from = this;
	var	diff = ((until.getTime() - from.getTime()) / 1000),
		day_diff = Math.floor(diff / 86400);

	if ( isNaN(day_diff) || day_diff < 0 || day_diff >= 31 )
		return this.strftime("%Y-%m-%d %H:%M:%S");

	return day_diff == 0 && (
			diff < 60 && "только что" ||
			diff < 120 && "1 минуту назад" ||
			diff < 3600 && Math.floor( diff / 60 ) + " минут назад" ||
			diff < 7200 && "1 час {0} минут назад".format(Math.floor((diff - 3600 * Math.floor(diff / 3600)) / 60)) ||
			diff < 86400 && Math.floor( diff / 3600 ) + " часов {0} минут назад"
                .format(Math.floor((diff - 3600 * Math.floor(diff / 3600)) / 60))) ||
		day_diff == 1 && "Вчера" ||
		day_diff < 7 && day_diff + " дней назад" ||
		day_diff < 31 && Math.ceil( day_diff / 7 ) + " недель назад";
};
var template = function(str){var fn = new Function('obj', 'var __p=[],print=function(){__p.push.apply(__p,arguments);};with(obj||{}){__p.push(\''+str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/<%=([\s\S]+?)%>/g,function(match,code){return "',"+code.replace(/\\'/g, "'")+",'";}).replace(/<%([\s\S]+?)%>/g,function(match,code){return "');"+code.replace(/\\'/g, "'").replace(/[\r\n\t]/g,' ')+"__p.push('";}).replace(/\r/g,'\\r').replace(/\n/g,'\\n').replace(/\t/g,'\\t')+"');}return __p.join('');");return fn;};
var JST = {};
document.addEventListener('DOMContentLoaded', function () {
    var $ = jQuery;
    $('script[type="text/x-jstemplate"]').each(function(){
        JST[$(this).attr('id')] = template($(this).html());
    });
}, false);
function ui_alert(html, settings, timeout, after_close){
    var dlg = $(JST.alert($.extend(settings, {body: html}))).modal();
    dlg.on("hidden.bs.modal", function(e){
        dlg.remove();
        if(typeof after_close != "undefined")
        {
            after_close();
        }
    });
    if (typeof timeout != "undefined")
    {
        (function(d, t){
            setTimeout(function(){
                try
                {
                    d.modal('hide');
                }
                catch(e){}
            }, t);
        })(dlg, timeout);
    }
    if(settings.buttons)
    {
        dlg.find('.modal-footer .pop-up__btn').each(function(idx, btn){
            $(btn).click(function(){
                var callback = settings.buttons[idx].callback;
                callback && callback();
                dlg.modal('hide');
            });
        });
    }
    dlg.modal("show");
};
function ui_confirm(html, settings, callback) {
    var buttons = [{name: 'Да', cls: 'btn-primary', callback: callback},
        {name: 'Нет', cls: 'btn-grey', callback: null}];
    ui_alert(html, $.extend(settings, {buttons: buttons}));
}
var display_errors = tco(function($form, errors, field_name, only_set_class){
    if(typeof  only_set_class === "undefined") {
        only_set_class = false;
    }
    $.each(errors, function(key, error){
        if(typeof error == 'string')
        {
            var $input = $form.find('[name="{0}"]'.format(field_name));
            var input_id = null;
            if($input.is(':hidden') && $input.is('select'))
            {
                input_id = $input.attr('id');
                $input = $input.next('.bootstrap-select');
            }
            if(field_name == '__all__' || $input.length == 0 || ($input.attr('type') != 'file' && $input.is(':hidden')))
            {
                if(field_name != '__all__')
                {
                    error = field_name+': '+error;
                }
                if(only_set_class === false) {
                    $form.prepend('<div class="error_{1}_all alert alert-danger" id="error_{1}_all">{0}</div>'.format(error, key));
                }
            }
            else if($input.attr('type') === 'file' && $input.closest('.photo-cont').length > 0)
            {
                $input.closest('.form-group').addClass('has-error');
                if(only_set_class === false) {
                    $input.closest('.photo-cont').after('<div id="error_{0}" class="alert alert-danger">{1}</div>'.format(key+'_'+($input.attr('id') || input_id), error));
                }
            }
            else if($input.attr('type') != 'hidden')
            {
                $input.closest('.form-group').addClass('has-error');
                if(only_set_class === false) {
                    $input.after('<div id="error_{0}" class="alert alert-danger">{1}</div>'.format(key + '_' + ($input.attr('id') || input_id), error));
                }
            }
        }
        else
        {
            if(/^subform_/.test(key))
            {
                var match = key.match(/^subform_(.+)/);
                if(match)
                {
                    var group = $form.find('[data-prefix^="{0}"]'.format(match[1]));
                    if(group.length)
                    {
                        error.forEach(function(er, idx){
                            $.each(er, function(f_name, ferrors){
                                if(Array.isArray(ferrors))
                                {
                                    var new_name = '{0}-{1}-{2}'.format(group.attr('data-prefix'), idx, f_name);
                                    error[idx][new_name] = ferrors;
                                    delete error[idx][f_name];
                                }
                            });
                        });
                    }
                }
            }
            return display_errors($form, error, key, only_set_class);
        }
    });
});

function after_submit($form, data) {
    $form.removeClass('has-error');
    $form.find('.has-error').removeClass('has-error');
    $form.find('[id^="error_"], [class^="error_"]').remove();
    if(typeof data == 'string')
    {
        $('.pagination__content').html(data);
    }
    else if(data.status == false && data.form_errors){
        $form.addClass('has_error');
        display_errors($form, data.form_errors);
        var scroll_to = $form.find('[id^="error_"]:visible:first');
        if(scroll_to.length)
        {
            $('html, body').animate(
                {'scrollTop': scroll_to.offset().top - 30}
            );
        }
    }
    else if(data.status == true && data.redirect_to && data.message)
    {
        var settings = {}
        if(data.title)
        {
            settings.title = data.title;
        }
        ui_alert(data.message, settings, 3000, function(){
            document.location.assign(data.redirect_to);
        });
    }
    else if(data.status == true && data.redirect_to)
    {
        document.location.assign(data.redirect_to);
    }
    else if(data.status == true && data.page_reload)
    {
        ui_alert('Спасибо, Ваш запрос принят', {title: 'Ваш запрос'}, 3000);
        setTimeout(function() {	
            window.location.reload();
        }, 500);
    }
    else if(data.message)
    {
        if(data.status == true)
        {
            $('.modal.show').modal('hide');
        }
        var settings = {}
        if(data.title)
        {
            settings.title = data.title;
        }
        try {
            ui_alert(data.message, settings);
        } catch (e) {

        }
    }
    else if(data.status == true && data.post_to)
    {
        $form.attr('action', data.post_to);
        $form.hide();
        if(data.cleaned_data.new_form)
        {
            $form.find(':input').remove();
            $.each(data.cleaned_data.new_form, function(field_name, value){
                $form.append('<input name="{0}" type="hidden" value="{1}" />'.format(field_name, value));
            });
        }
        else
        {
          $.each(data.cleaned_data, function(field_name, value){
            $form.find('[name*="{0}"]'.format(field_name)).val(value).removeAttr('disabled');
          });
        }
        $form.removeClass('ajax');
        var iframe_name = 'iframe_{0}'.format($form.attr('id'));
        $form.before('<iframe name="{0}" id="{0}"></iframe>'.format(iframe_name));
        $form.attr('target', iframe_name);
        $form.submit();
    }
}

(function($) {

  var o = $({});

  $.subscribe = function() {
    o.on.apply(o, arguments);
  };

  $.unsubscribe = function() {
    o.off.apply(o, arguments);
  };

  $.publish = function() {
    o.trigger.apply(o, arguments);
  };

}(jQuery));

function uuid4() {
    var uuid = '', ii;
    for (ii = 0; ii < 32; ii += 1) {
        switch (ii) {
            case 8:
            case 20:
                uuid += '-';
                uuid += (Math.random() * 16 | 0).toString(16);
                break;
            case 12:
                uuid += '-';
                uuid += '4';
                break;
            case 16:
                uuid += '-';
                uuid += (Math.random() * 4 | 8).toString(16);
                break;
            default:
                uuid += (Math.random() * 16 | 0).toString(16);
        }
    }
    return uuid;
}
var docCookies = {
  getItem: function (sKey) {
    if (!sKey) { return null; }
    return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
  },
  setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
    if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
    var sExpires = "";
    if (vEnd) {
      switch (vEnd.constructor) {
        case Number:
          sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
          break;
        case String:
          sExpires = "; expires=" + vEnd;
          break;
        case Date:
          sExpires = "; expires=" + vEnd.toUTCString();
          break;
      }
    }
    document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
    return true;
  },
  removeItem: function (sKey, sPath, sDomain) {
    if (!this.hasItem(sKey)) { return false; }
    document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
    return true;
  },
  hasItem: function (sKey) {
    if (!sKey) { return false; }
    return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
  },
  keys: function () {
    var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
    for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
    return aKeys;
  }
};