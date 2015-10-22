<?php
/**
 * 安全cookie
 * 对cookie存取进行加密
 */
class Cookie
{
	private static $_config   = null;  //配置
	private static $_disabled = false; //是否禁用写入
	/**
	 * 设置cookie
	 * @method set
	 * @param  [string] $name   [cookie名称]
	 * @param  [mixed] $value  [cookie值]
	 * @param  [string] $path   [存取路径]
	 * @param  [int] $expire 有效时间
	 * @author NewFuture
	 */
	public static function set($name, $value, $path = '', $expire = null)
	{
		if (self::$_disabled)
		{
			$_COOKIE[$name] = $value;
		}
		elseif ($value = self::encode($value))
		{
			$path   = $path ?: self::config('path');
			$expire = $expire ? ($_SERVER['REQUEST_TIME'] + $expire) : null;
			return setcookie($name, $value, $expire, $path, self::config('domain'), self::config('secure'), self::config('httponly'));
		}
	}

	/**
	 * 获取cookie
	 * @method get
	 * @param  [string] $name [cookie名称]
	 * @return [json]
	 * @author NewFuture
	 */
	public static function get($name)
	{
		if (isset($_COOKIE[$name]) && $data = $_COOKIE[$name])
		{
			return self::$_disabled ? $data : self::decode($data);
		}
	}

	/**
	 * 删除
	 * @method del
	 * @param  [string] $name [cookie名称]
	 * @author NewFuture
	 */
	public static function del($name, $path = null)
	{
		if (isset($_COOKIE[$name]))
		{
			unset($_COOKIE[$name]);
			if (!self::$_disabled)
			{
				$path = $path ?: self::config('path');
				setcookie($name, '', 100, $path, self::config('domain'), self::config('secure'), self::config('httponly'));
			}
		}
	}

	/**
	 * 清空cookie
	 */
	public static function flush()
	{
		if (empty($_COOKIE))
		{
			return null;
		}
		elseif (self::$_disabled)
		{
			unset($_COOKIE);
		}
		else
		{
			/*逐个删除*/
			foreach ($_COOKIE as $key => $val)
			{
				self::del($key);
			}
		}

	}

	/**
	 * 禁用cookie，不回读取和写入客户端
	 * @method disable
	 * @author NewFuture
	 */
	public static function disable()
	{
		self::$_disabled = true;
	}

	/**
	 * 启用Cookie
	 * @method enable
	 * @author NewFuture
	 */
	public static function enable()
	{
		self::$_disabled = false;
	}

	/**
	 * Cookie数据加密编码
	 * @method encode
	 * @param  [type] $data         [description]
	 * @return [type] [description]
	 * @author NewFuture
	 */
	public static function encode($data)
	{
		return Encrypt::aesEncode(json_encode($data), self::config('key'), true);
	}

	/**
	 * Cookie数据解密
	 * @method encode
	 * @param  [type] $data         [description]
	 * @return [type] [description]
	 * @author NewFuture
	 */
	public static function decode($data)
	{
		if ($data = Encrypt::aesDecode($data, self::config('key'), true))
		{
			return @json_decode($data);
		}
	}

	/**
	 * 获取cookie配置
	 * @method config
	 * @param  [string] $name [配置变量名]
	 * @return [mixed]       [description]
	 * @author NewFuture
	 */
	private static function config($name)
	{
		if (!$config = self::$_config)
		{
			$config = Config::get('cookie');
			if (!$key = Kv::get('COOKIE_aes_key'))
			{
				/*重新生成加密密钥*/
				$key = Random::word(32);
				Kv::set('COOKIE_aes_key', $key);
			}
			$config['key'] = $key;
			self::$_config = $config;
		}
		return isset($config[$name]) ? $config[$name] : null;
	}
}