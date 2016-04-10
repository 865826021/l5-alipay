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
		], 'lemon');
	}

	/**
	 * Register the service provider.
	 * @return void
	 */
	public function register() {
		// 配置文件合并
		$this->mergeConfigFrom(__DIR__ . '/../config/alipay.php', 'sl-alipay');

		$this->app->bind('lemon.alipay.web-direct', function ($app) {
			$alipay = new WebDirect\SdkPayment();
			/** @type ConfigRepository $config */
			$config = $app->config;
			$alipay->setPartner($config->get('sl-alipay.partner_id'))
				->setSellerId($config->get('sl-alipay.seller_id'))
				->setKey($config->get('sl-alipay.web_direct_key'))
				->setSignType($config->get('sl-alipay.web_direct_sign_type'))
				->setNotifyUrl($config->get('sl-alipay.web_direct_notify_url'))
				->setReturnUrl($config->get('sl-alipay.web_direct_return_url'))
				->setExterInvokeIp($app->request->getClientIp());
			return $alipay;
		});
	}

	/**
	 * Get the services provided by the provider.
	 * @return array
	 */
	public function provides() {
		return ['lemon.alipay.web-direct'];
	}

}
