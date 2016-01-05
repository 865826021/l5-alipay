# Alipay 在 Laravel 5 的封装包

Fork from [Latrell/Alipay](https://github.com/Latrell/Alipay)

该拓展包想要达到在Laravel5框架下，便捷使用支付宝的目的。

## 安装

```
composer require imvkmark/l5-alipay dev-master
```

更新你的依赖包 ```composer update``` 或者全新安装 ```composer install```。


## 使用

要使用支付宝SDK服务提供者，你必须自己注册服务提供者到Laravel服务提供者列表中。
基本上有两种方法可以做到这一点。

找到 `config/app.php` 配置文件中，key为 `providers` 的数组，在数组中添加服务提供者。

```php
    'providers' => [
        // ...
        Imvkmark\L5Alipay\L5AlipayServiceProvider::class,
    ]
```

运行 `php artisan vendor:publish` 命令，发布配置文件到你的项目中。

配置文件 `config/lm-alipay.php` 为公共配置信息文件， `web_direct_` 为Web版支付宝SDK配置前缀。

## 例子

### 支付申请

#### Web 版即时到账 (web_direct)

```php
// 创建支付单。
$alipay = app('lemon.alipay.web-direct');
$alipay->setOutTradeNo('order_id');
$alipay->setTotalFee('order_price');
$alipay->setSubject('goods_name');
$alipay->setBody('goods_description');
//该设置为可选，添加该参数设置，支持二维码支付。
$alipay->setQrPayMode(2);

// 跳转到支付页面。
return redirect()->to($alipay->getPayLink());
```

### 结果通知

#### 网页

```php
	/**
	 * 异步通知
	 */
	public function webNotify()
	{
		// 验证请求。
		if (! app('lemon.alipay.web-direct')->verify()) {
			Log::notice('Alipay notify post data verification fail.', [
				'data' => Request::instance()->getContent()
			]);
			return 'fail';
		}

		// 判断通知类型。
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: 支付成功，取得订单号进行其它相关操作。
				Log::debug('Alipay notify post data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}

		return 'success';
	}

	/**
	 * 同步通知
	 */
	public function webReturn()
	{
		// 验证请求。
		if (! app('lemon.alipay.web-direct')->verify()) {
			Log::notice('Alipay return query data verification fail.', [
				'data' => Request::getQueryString()
			]);
			return view('alipay.fail');
		}

		// 判断通知类型。
		switch (Input::get('trade_status')) {
			case 'TRADE_SUCCESS':
			case 'TRADE_FINISHED':
				// TODO: 支付成功，取得订单号进行其它相关操作。
				Log::debug('Alipay notify get data verification success.', [
					'out_trade_no' => Input::get('out_trade_no'),
					'trade_no' => Input::get('trade_no')
				]);
				break;
		}

		return view('alipay.success');
	}
```
