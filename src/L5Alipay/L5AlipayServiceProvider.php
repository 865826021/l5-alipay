<?php namespace Imvkmark\L5Alipay;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class L5AlipayServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 * @return void
	 */
	public function boot() {
		// 加载的时候进行配置项的发布
		$this->publishes([
			__DIR__ . '/../config/alipay.php' => config_path('l5-alipay.php'),
		], 'sour-lemon');
	}

	/**
	 * Register the service provider.
	 * @return void
	 */
	public function register() {
		// 配置文件合并
		$this->mergeConfigFrom(__DIR__ . '/../config/alipay.php', 'l5-alipay');

		$this->app->bind('l5.alipay.web-direct', function ($app) {
			$alipay = new WebDirect\SdkPayment();
			/** @type ConfigRepository $config */
			$config = $app->config;
			$alipay->setPartner($config->get('l5-alipay.partner_id'))
				->setSellerId($config->get('l5-alipay.seller_id'))
				->setKey($config->get('l5-alipay.web_direct_key'))
				->setSignType($config->get('l5-alipay.web_direct_sign_type'))
				->setNotifyUrl($config->get('l5-alipay.web_direct_notify_url'))
				->setReturnUrl($config->get('l5-alipay.web_direct_return_url'))
				->setExterInvokeIp($app->request->getClientIp());
			return $alipay;
		});

		$this->app->bind('l5.alipay.mobile', function ($app) {
			$alipay = new Mobile\SdkPayment();

			$alipay->setPartner($app->config->get('l5-alipay.partner_id'))
				->setSellerId($app->config->get('l5-alipay.seller_id'))
				->setSignType($app->config->get('l5-alipay.mobile_sign_type'))
				->setPrivateKeyPath($app->config->get('l5-alipay.mobile_private_key_path'))
				->setPublicKeyPath($app->config->get('l5-alipay.mobile_public_key_path'))
				->setNotifyUrl($app->config->get('l5-alipay.mobile_notify_url'));

			return $alipay;
		});

	}

	/**
	 * Get the services provided by the provider.
	 * @return array
	 */
	public function provides() {
		return [
			'l5.alipay.web-direct',
			'l5.alipay.mobile',
		];
	}

}
