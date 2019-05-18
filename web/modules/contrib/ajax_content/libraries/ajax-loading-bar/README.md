jquery-loading-bar
==================

this is  little jQuery plugin that will add a loading bar in your page

这是一个简单的jquery插件，用于在使用jquery的get，post方式时，会自动在页面顶部添加一个进度条(loading-bar)。

###使用
1、在页面header里面添加如下三个文件：
<pre>
<code>
&lt;link rel="stylesheet" href="loading-bar.css"/&gt;
&lt;script src="jquery-1.7.2.min.js"&gt;&lt;/script&gt;
&lt;script src="jquery-loading-bar.js"&gt;&lt;/script$gt;
</code>
</pre>

2、当在使用jquery的ajax中get，post请求时,使用如下方法即可：
<pre>
&lt;script&gt;
    $(document).ready(function () {
        $.get('text.txt', {}, function (data) {
            console.log(data)
        });

        $.post('jquery-1.7.2.min.js', {}, function (data) {
            console.log(data)
        });
    });
&lt;/script&gt;


</pre>

即,将平常中使用的$.get，$.post方法即可，其他传参均按照jquery中使用get或者post方法一致。
