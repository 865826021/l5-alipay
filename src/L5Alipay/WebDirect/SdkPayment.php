<?php namespace Imvkmark\L5Alipay\WebDirect;

/*
 * Web 即时到帐
 * @url https://doc.open.alipay.com/doc2/detail.htm?spm=0.0.0.0.s8xViV&treeId=62&articleId=103566&docType=1
 * @author     Mark <zhaody901@126.com>
 * @copyright  Copyright (c) 2013-2015 Sour Lemon Team
 */
class SdkPayment {

	/** @type string 支付宝网关地址（新） */
	private $__gateway_new = 'https://mapi.alipay.com/gateway.do?';

	private $__https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

	private $__http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

	/** @type string 接口名称 */
	private $service = 'create_direct_pay_by_user';

	/**
	 * @type string
	 * 签约的支付宝账号对应的支付宝唯一用户号。
	 * 以2088开头的16位纯数字组成。
	 */
	private $partner;

	/**
	 * @type string
	 * 参数编码字符集
	 * 商户网站使用的编码格式，如utf-8、gbk、gb2312等。
	 */
	private $_input_charset = 'utf-8';

	/**
	 * @type string
	 * 签名方式
	 * DSA、RSA、MD5三个值可选，必须大写。
	 */
	private $sign_type = 'MD5';

	/**
	 * @type string
	 * 服务器异步通知页面路径
	 * 支付宝服务器主动通知商户网站里指定的页面http路径。
	 */
	private $notify_url;

	/**
	 * @type string
	 * 页面跳转同步通知页面路径
	 * 支付宝处理完请求后，当前页面自动跳转到商户网站里指定页面的http路径。
	 */
	private $return_url;

	/**
	 * @type string
	 * 请求出错时的通知页面路径
	 * 当商户通过该接口发起请求时，如果出现提示报错，支付宝会根据请求出错时的通知错误码通过异步的方式发送通知给商户。
	 */
	private $error_notify_url;

	/**
	 * @type string
	 * 商户网站唯一订单号
	 * 支付宝合作商户网站唯一订单号。
	 */
	private $out_trade_no;

	/**
	 * @type int
	 * 支付类型
	 * 取值范围请参见附录收款类型。默认值为：1（商品购买）。
	 * 注意：
	 * 支付类型为“47”时，公共业务扩展参数（extend_param）中必须包含凭证号（evoucheprod_evouche_id）参数名和参数值。
	 */
	private $payment_type = 1;

	/**
	 * @type string
	 * seller_id是以2088开头的纯16位数字。
	 */
	private $seller_id;

	/**
	 * @type float
	 * 该笔订单的资金总额，单位为RMB-Yuan。取值范围为[0.01，100000000.00]，精确到小数点后两位。
	 */
	private $total_fee;

	/**
	 * @type string
	 * 商品名称
	 * 商品的标题/交易标题/订单标题/订单关键字等。该参数最长为128个汉字。
	 */
	private $subject;

	/**
	 * @type string
	 * 对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。
	 */
	private $body;

	private $it_b_pay;

	/**
	 * @type string
	 * 收银台页面上，商品展示的超链接。
	 */
	private $show_url;

	private $anti_phishing_key;

	private $exter_invoke_ip;

	private $key;

	private $transport;

	private $cacert;

	/**
	 * @type int
	 * 扫码支付方式
	 * 前置模式是将二维码前置到商户的订单确认页的模式。需要商户在自己的页面中以iframe方式请求支付宝页面。具体分为以下3种：
	 * 0：订单码-简约前置模式，对应iframe宽度不能小于600px，高度不能小于300px；
	 * 1：订单码-前置模式，对应iframe宽度不能小于300px，高度不能小于600px；
	 * 3：订单码-迷你前置模式，对应iframe宽度不能小于75px，高度不能小于75px。
	 * 跳转模式下，用户的扫码界面是由支付宝生成的，不在商户的域名下。
	 * 2：订单码-跳转模式
	 */
	private $qr_pay_mode;

	public function __construct() {
		$this->cacert = getcwd() . '/../../resources/cacert.pem';
	}

	/**
	 * 取得支付链接
	 */
	public function getPayLink() {
		$parameter = [
			'service'           => $this->service,
			'partner'           => $this->partner,
			'payment_type'      => $this->payment_type,
			'notify_url'        => $this->notify_url,
			'return_url'        => $this->return_url,
			'seller_email'      => $this->seller_id,
			'out_trade_no'      => $this->out_trade_no,
			'subject'           => $this->subject,
			'total_fee'         => $this->total_fee,
			'body'              => $this->body,
			'it_b_pay'          => $this->it_b_pay,
			'show_url'          => $this->show_url,
			'anti_phishing_key' => $this->anti_phishing_key,
			'exter_invoke_ip'   => $this->exter_invoke_ip,
			'_input_charset'    => strtolower($this->_input_charset),
			'qr_pay_mode'       => $this->qr_pay_mode,
		];

		$para = $this->_buildRequestPara($parameter);

		return $this->__gateway_new . $this->_createLinkstringUrlencode($para);
	}

	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp array 请求前的参数数组
	 * @return array 要请求的参数数组
	 */
	private function _buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->_paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->_argSort($para_filter);

		//生成签名结果
		$mysign = $this->_buildRequestMysign($para_sort);

		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign']      = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->sign_type));

		return $para_sort;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $para array 签名参数组
	 * @return array 去掉空值与签名参数后的新签名参数组
	 */
	private function _paraFilter($para) {
		$para_filter = [];
		while ((list ($key, $val) = each($para)) == true) {
			if ($key == 'sign' || $key == 'sign_type' || $val == '') {
				continue;
			} else {
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * 对数组排序
	 * @param $para array 排序前的数组
	 * @return array 排序后的数组
	 */
	private function _argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}

	/**
	 * 生成签名结果
	 * @param $para_sort array 已排序要签名的数组
	 * @return string 签名结果字符串
	 */
	private function _buildRequestMysign($para_sort) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->_createLinkstring($para_sort);

		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$mysign = $this->_md5Sign($prestr, $this->key);
				break;
			default:
				$mysign = '';
		}

		return $mysign;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para array 需要拼接的数组
	 * @return string 拼接完成以后的字符串
	 */
	private function _createLinkstring($para) {
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . $val . '&';
		}
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);

		return $arg;
	}

	/**
	 * 签名字符串
	 * @param $prestr string 需要签名的字符串
	 * @param $key    string 私钥
	 * @return string 签名结果
	 */
	private function _md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
	 * @param $para array 需要拼接的数组
	 * @return string 拼接完成以后的字符串
	 */
	private function _createLinkstringUrlencode($para) {
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . urlencode($val) . '&';
		}
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);

		return $arg;
	}

	/**
	 * 验证消息是否是支付宝发出的合法消息
	 */
	public function verify() {
		// 判断请求是否为空
		if (empty($_POST) && empty($_GET)) {
			return false;
		}

		$data = $_POST ?: $_GET;

		// 生成签名结果
		$is_sign = $this->_getSignVerify($data, $data['sign']);

		// 获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
		$response_txt = 'true';
		if (!empty($data['notify_id'])) {
			$response_txt = $this->_getResponse($data['notify_id']);
		}

		// 验证
		// $response_txt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
		// isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
		if (preg_match('/true$/i', $response_txt) && $is_sign) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取返回时的签名验证结果
	 * @param $para_temp string 通知返回来的参数数组
	 * @param $sign      string   返回的签名结果
	 * @return string 签名验证结果
	 */
	private function _getSignVerify($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->_paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->_argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->_createLinkstring($para_sort);

		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$is_sgin = $this->_md5Verify($prestr, $sign, $this->key);
				break;
			default:
				$is_sgin = false;
		}

		return $is_sgin;
	}

	/**
	 * 验证签名
	 * @param $prestr string 需要签名的字符串
	 * @param $sign   string  签名结果
	 * @param $key    string    私钥
	 * @return bool 签名结果
	 */
	private function _md5Verify($prestr, $sign, $key) {
		$prestr = $prestr . $key;
		$mysgin = md5($prestr);

		if ($mysgin == $sign) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 获取远程服务器ATN结果,验证返回URL
	 * @param $notify_id string 通知校验ID
	 * @return string 服务器ATN结果
	 *                   验证结果集：
	 *                   invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
	 *                   true 返回正确信息
	 *                   false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	 */
	private function _getResponse($notify_id) {
		$transport = strtolower(trim($this->transport));
		$partner   = trim($this->partner);
		if ($transport == 'https') {
			$veryfy_url = $this->__https_verify_url;
		} else {
			$veryfy_url = $this->__http_verify_url;
		}
		$veryfy_url   = $veryfy_url . 'partner=' . $partner . '&notify_id=' . $notify_id;
		$response_txt = $this->_getHttpResponseGET($veryfy_url, $this->cacert);

		return $response_txt;
	}

	/**
	 * 远程获取数据，GET模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url        string 指定URL完整路径地址
	 * @param $cacert_url string 指定当前工作目录绝对路径
	 * @return mixed 远程输出的数据
	 */
	private function _getHttpResponseGET($url, $cacert_url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
		curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
		$responseText = curl_exec($curl);
		//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		//var_dump( curl_error($curl) );
		curl_close($curl);

		return $responseText;
	}

	public function setPartner($partner) {
		$this->partner = $partner;
		return $this;
	}

	public function setNotifyUrl($notify_url) {
		$this->notify_url = $notify_url;
		return $this;
	}

	public function setReturnUrl($return_url) {
		$this->return_url = $return_url;
		return $this;
	}

	public function setOutTradeNo($out_trade_no) {
		$this->out_trade_no = $out_trade_no;
		return $this;
	}

	public function setKey($key) {
		$this->key = $key;
		return $this;
	}

	public function setSellerId($seller_id) {
		$this->seller_id = $seller_id;
		return $this;
	}

	public function setTotalFee($total_fee) {
		$this->total_fee = $total_fee;
		return $this;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
		return $this;
	}

	public function setBody($body) {
		$this->body = $body;
		return $this;
	}

	public function setItBPay($it_b_pay) {
		$this->it_b_pay = $it_b_pay;
		return $this;
	}

	public function setShowUrl($show_url) {
		$this->show_url = $show_url;
		return $this;
	}

	public function setSignType($sign_type) {
		$this->sign_type = $sign_type;
		return $this;
	}

	public function setExterInvokeIp($exter_invoke_ip) {
		$this->exter_invoke_ip = $exter_invoke_ip;
		return $this;
	}

	public function setQrPayMode($qr_pay_mode) {
		$this->qr_pay_mode = $qr_pay_mode;
		return $this;
	}

	/**
	 * 远程获取数据，POST模式
	 * 注意：
	 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
	 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
	 * @param $url           string  指定URL完整路径地址
	 * @param $cacert_url    string  指定当前工作目录绝对路径
	 * @param $para          string 请求的数据
	 * @param $input_charset string 编码格式。默认值：空值
	 * @return mixed 远程输出的数据
	 */
	private function _getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

		if (trim($input_charset) != '') {
			$url = $url . "_input_charset=" . $input_charset;
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
		curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_POST, true); // post传输数据
		curl_setopt($curl, CURLOPT_POSTFIELDS, $para);// post传输数据
		$responseText = curl_exec($curl);
		//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		//var_dump( curl_error($curl) );
		curl_close($curl);

		return $responseText;
	}

	/**
	 * 实现多种字符编码方式
	 * @param $input           string 需要编码的字符串
	 * @param $_output_charset string 输出的编码格式
	 * @param $_input_charset  string 输入的编码格式
	 *                         return 编码后的字符串
	 * @return mixed|string 需要编码的字符串
	 */
	private function _charsetEncode($input, $_output_charset, $_input_charset) {
		if (!isset($_output_charset)) $_output_charset = $_input_charset;
		if ($_input_charset == $_output_charset || $input == null) {
			$output = $input;
		} elseif (function_exists("mb_convert_encoding")) {
			$output = mb_convert_encoding($input, $_output_charset, $_input_charset);
		} elseif (function_exists("iconv")) {
			$output = iconv($_input_charset, $_output_charset, $input);
		} else die("sorry, you have no libs support for charset change.");
		return $output;
	}

	/**
	 * 实现多种字符解码方式
	 * @param $input           string 需要解码的字符串
	 * @param $_output_charset string 输出的解码格式
	 * @param $_input_charset  string  输入的解码格式
	 * @return mixed|string 解码后的字符串
	 */
	private function _charsetDecode($input, $_input_charset, $_output_charset) {
		if (!isset($_output_charset)) $_output_charset = $_input_charset;
		if ($_input_charset == $_output_charset || $input == null) {
			$output = $input;
		} elseif (function_exists("mb_convert_encoding")) {
			$output = mb_convert_encoding($input, $_output_charset, $_input_charset);
		} elseif (function_exists("iconv")) {
			$output = iconv($_input_charset, $_output_charset, $input);
		} else die("sorry, you have no libs support for charset changes.");
		return $output;
	}

	/**
	 * 生成要请求给支付宝的参数数组
	 * @param $para_temp array 请求前的参数数组
	 * @return string 要请求的参数数组字符串
	 */
	function buildRequestParaToString($para_temp) {
		//待请求参数数组
		$para = $this->_buildRequestPara($para_temp);

		//把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
		$request_data = $this->_createLinkstringUrlencode($para);

		return $request_data;
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $para_temp   array 请求参数数组
	 * @param $method      string 提交方式。两个值可选：post、get
	 * @param $button_name string 确认按钮显示文字
	 * @return string 提交表单HTML文本
	 */
	function buildRequestForm($para_temp, $method, $button_name) {
		//待请求参数数组
		$para = $this->_buildRequestPara($para_temp);

		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->__gateway_new . "_input_charset=" . trim(strtolower($this->_input_charset)) . "' method='" . $method . "'>";
		while (list ($key, $val) = each($para)) {
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
		}

		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit' value='" . $button_name . "'></form>";

		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";

		return $sHtml;
	}

	/**
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
	 * @param $para_temp string 请求参数数组
	 * @return string 支付宝处理结果
	 */
	function buildRequestHttp($para_temp) {
		//待请求参数数组字符串
		$request_data = $this->_buildRequestPara($para_temp);

		//远程获取数据
		$sResult = $this->_getHttpResponsePOST($this->__gateway_new, $this->cacert, $request_data, trim(strtolower($this->_input_charset)));

		return $sResult;
	}

	/**
	 * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
	 * @param $para_temp      array 请求参数数组
	 * @param $file_para_name string 文件类型的参数名
	 * @param $file_name      string  文件完整绝对路径
	 * @return string 支付宝返回处理结果
	 */
	function buildRequestHttpInFile($para_temp, $file_para_name, $file_name) {

		//待请求参数数组
		$para                  = $this->_buildRequestPara($para_temp);
		$para[$file_para_name] = "@" . $file_name;

		//远程获取数据
		$sResult = $this->_getHttpResponsePOST($this->__gateway_new, $this->cacert, $para, trim(strtolower($this->_input_charset)));

		return $sResult;
	}

	/**
	 * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	 * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
	 * return 时间戳字符串
	 */
	function queryTimestamp() {
		$url = $this->__gateway_new . "service=query_timestamp&partner=" . trim(strtolower($this->partner)) . "&_input_charset=" . trim(strtolower($this->_input_charset));

		$doc = new \DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName("encrypt_key");
		$encrypt_key     = $itemEncrypt_key->item(0)->nodeValue;

		return $encrypt_key;
	}
}
