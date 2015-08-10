<?php

namespace MyNamespace\PayPal;

// Composer Loader
include_once 'composer/vendor/autoload.php';
use PayPal\Cache\AuthorizationCache as PayPal_AuthorizationCache;


/**
 * Special modification introduced by Matias Perrone for aluGuest.com
 * @author		Matias Perrone <matias@aluguest.com>
 * @uses		Mongo class
 * @copyright	2015 aluGuest.com (aluGuest LLC)
 */

class AuthorizationCache extends PayPal_AuthorizationCache
{
	public static $mongo = false;
	public static $colection = 'PayPalAuthorizationCache';

	public static function init()
	{
		if ( !self::$mongo )
    		self::$mongo = new MyNamespace\MyMongo();
	}

    /**
     * A pull method which would read the persisted data based on clientId.
     * If clientId is not provided, an array with all the tokens would be passed.
     *
     * @param array|null $config
     * @param string $clientId
     * @return mixed|null
     */
    public static function pull($config = null, $clientId = null)
    {
        // Return if not enabled
        if (!self::isEnabled($config)) { return null; }

    	self::init();

    	$tokens = null;
    	$find = array();
		$qty = 100;
		$page = 1;
		$total = null;
    	$fields = array('_id' => false);

		if ($clientId)
		{
			$find['clientId'] = $clientId;
			$tokens = self::$mongo->getItem(self::$colection, $find, $fields);
			if (!$tokens)
				$tokens = null;
		}
		else
		{
			$tokens = self::$mongo->getAll(self::$colection, $qty, $page, $total, $find, $fields);
		}
        return $tokens;
    }

    /**
     * Persists the data into the "mongo" cache
     *
     * @param array|null $config
     * @param      $clientId
     * @param      $accessToken
     * @param      $tokenCreateTime
     * @param      $tokenExpiresIn
     * @throws \Exception
     */
    public static function push($config = null, $clientId, $accessToken, $tokenCreateTime, $tokenExpiresIn)
    {
        // Return if not enabled
        if (!self::isEnabled($config)) { return null; }

    	self::init();

        $token = array(
            'clientId' => $clientId,
            'accessTokenEncrypted' => $accessToken,
            'tokenCreateTime' => $tokenCreateTime,
            'tokenExpiresIn' => $tokenExpiresIn
        );
    	$expireAfterSeconds = $tokenExpiresIn;
        $ok = self::$mongo->saveData(self::$colection, 'clientId', $token, $expireAfterSeconds, true);
        if (!$ok)
        {
            throw new \Exception("Failed to write cache");
        };
    }

}