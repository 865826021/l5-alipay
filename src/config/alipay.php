<?php
return [
	/*
	|--------------------------------------------------------------------------
	| 支付宝公共配置项目
	|--------------------------------------------------------------------------
	*/

	// 合作身份者id，以2088开头的16位纯数字。
	'partner_id'            => 'L5_ALIPAY_PARTNER_ID',

	// 卖家支付宝帐户
	'seller_id'             => 'L5_ALIPAY_SELL_ID',


	/*
	|--------------------------------------------------------------------------
	| 即时到账 配置项目
	|--------------------------------------------------------------------------
	*/

	// 安全检验码，以数字和字母组成的32位字符
	'web_direct_key'        => 'L5_ALIPAY_DIRECT_PAY_KEY',

	// 签名方式
	'web_direct_sign_type'  => 'MD5',

	// 服务器异步通知页面路径
	'web_direct_notify_url' => 'charge-notify',

	// 页面跳转同步通知页面路径
	'web_direct_return_url' => 'charge-callback',

];