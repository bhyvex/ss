<?php if (!defined('THINK_PATH')) exit();?>﻿<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>登录超时，请先登录系统</title>
<meta name="description" content="" />
<style type="text/css">
#wrapper{text-align:center;margin:100px auto;width:594px;}
</style>
<script type="text/javascript" src="__ROOT__/Public/js/jquery-1.8.1.min.js"></script>
</head>
<body>
    <div id="wrapper">
        <a href="#"><img src="__ROOT__/Public/img/login_logo_130725.png"></a>
        <div>
            <h1>唉呀!</h1>
            <p>您已经登录超时！</p>
            <a class="link" href="./login.html" title="点击跳转到登录页面"><span id="sec">5</span> 秒后跳转到登录页面</a>
			<br /><br /><br />
			<!--a class="link" href="404.html">查看JavaScript版本404页面跳转</a-->
        </div>
    </div>
	
	<script type="text/javascript">
	$(function () {
	   setTimeout("lazyGo();", 1000);
	});
	function lazyGo() {
		var sec = $("#sec").text();
		$("#sec").text(--sec);
		if (sec > 0)
			setTimeout("lazyGo();", 1000);
		else
			window.location.href = "./login.html";
	}
	</script>
</body>
</html>