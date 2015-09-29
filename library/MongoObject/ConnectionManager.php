<?php

namespace MongoObject;

/**
 * Manager połaczeń z bazami danych
 *
 * @author Mateusz Juściński
 */
class ConnectionManager
{
	/**
	 * Server connections array
	 *
	 * @var	array
	 */
	private $aConnections = [];

	/**
	 * Connections to DBs
	 *
	 * @var	array
	 */
	private $aDbs = [];

	/**
	 * Nazwa domyślnej bazy danych
	 *
	 * @var	string|null
	 */
	private $sDefaultDb = null;

	/**
	 * Nazwa domyślnego połączenia
	 *
	 * @var	string|null
	 */
	private $sDefaultConn = null;

// static

	/**
	 * Instancja globalnego managera
	 *
	 * @var	\MongoObject\ConnectionManager
	 */
	private static $oInstance;

	/**
	 * Zwraca instancję globalnego ConnectionManagera
	 *
	 * @return	\MongoObject\ConnectionManager
	 */
	public static function getInstance()
	{
		if(empty(self::$oInstance))
		{
			self::$oInstance = new static;
		}

		return self::$oInstance;
	}

// instance

	/**
	 * Creates new connection to MongoDB and store it internally
	 *
	 * @param	string			$sUser		user name
	 * @param	string			$sPass		password
	 * @param	string|array	$mHosts		hosts name(s), IP(s)
	 * @param	string|null		$sDbName	(optional) database name
	 * @param	array			$aOptions	(optional) additional params
	 * @return	\MongoObject\ConnectionManager
	 */
	public function createNoAuth($sConnName, $mHosts, $sDbName = null, array $aOptions = [])
	{
		unset($aOptions['username'], $aOptions['password']);

		if(!is_array($mHosts))
		{
			$mHosts = [$mHosts];
		}

		// zestawiam połączenie
		$this->create($sConnName, $mHosts, $aOptions, $sDbName);

		return $this;
	}

	/**
	 * Creates new connection to MongoDB and store it internally
	 *
	 * @param	string			$sUser		user name
	 * @param	string			$sPass		password
	 * @param	string|array	$mHosts		hosts name(s), IP(s)
	 * @param	string|null		$sDbName	(optional) database name
	 * @param	array			$aOptions	(optional) additional params
	 * @return	\MongoObject\ConnectionManager
	 */
	public function createWithAuth($sConnName, $sUser, $sPass, $mHosts, $sDbName = null, array $aOptions = [])
	{
		$aOptions['password'] = $sPass;
		$aOptions['username'] = $sUser;

		if(!is_array($mHosts))
		{
			$mHosts = [$mHosts];
		}

		// zestawiam połączenie
		$this->create($sConnName, $mHosts, $aOptions, $sDbName);

		return $this;
	}

	/**
	 * Zwraca połączenie do bazy danych o podanej nazwie lub domyślne
	 *
	 * @param	string|null	$sName	opcjonalna nazwa połączenia
	 * @return	\MongoDb
	 */
	public function getDb($sDbName = null, $sConnName = null)
	{
		$sConnName	= $this->getConnName($sConnName);
		$sDbName	= $this->getDbName($sDbName);
		$sCacheName	= $sConnName .'|'. $sDbName;

		// haven't got cached connection
		if(empty($this->aDbs[$sCacheName]))
		{
			$this->aDbs[$sCacheName] = $this->getConnection($sConnName)->selectDB($sDbName);
		}

		return $this->aDbs[$sCacheName];
	}

	/**
	 * Zwraca połączenie do bazy danych o podanej nazwie lub domyślne
	 *
	 * @param	string|null	$sName	opcjonalna nazwa połączenia
	 * @return	\MongoDb
	 */
	public function getConnection($sName = null)
	{
		$sName = $this->getConnName($sName);

		// nie zdefiniowane połączeń
		if(empty($this->aConnections[$sName]))
		{
			throw new Exception('Połączenie z bazą danych "'. $sName .'" nie zostało zainicjalizowane');
		}

		return $this->aConnections[$sName];
	}

	/**
	 * Set default connection
	 *
	 * @param	string	$sConnName	connection name
	 * @param	string	$sDbName	database name
	 * @throws	Exception
	 */
	public function setDefault($sConnName, $sDbName = null)
	{
		// nie mam tego połączenia
		if(empty($this->aConnections[$sConnName]))
		{
			throw new Exception('Baza danych o nazwie "'. $sConnName .'" NIE została zdefiniowana');
		}

		$this->sDefaultConn = $sConnName;
		$this->sDefaultDb	= $sDbName;
	}

// protected

	/**
	 * Creates connection to MongoDB
	 *
	 * @param	string	$sConnName	unique connection name
	 * @param	array	$aHosts		list of hosts
	 * @param	array	$aOptions	additional connection options
	 * @param	string	$sDbName	name of database
	 * @return	\MongoClient
	 */
	protected function create($sConnName, array $aHosts, array $aOptions, $sDbName = null)
	{
		// tworzę connection string
		$sConnStr	= 'mongodb://'. implode(',', $aHosts) . (empty($sDbName) ? '' : '/'. $sDbName);
		$bIsDefault	= empty($this->aConnections);

		$this->aConnections[$sConnName] = new \MongoClient($sConnStr, $aOptions);

		// jeśli nie mam jeszcze żadnego połączenia to ustawiam 1 jako domyślne
		if($bIsDefault)
		{
			$this->setDefault($sConnName, $sDbName);
		}

		return $this->aConnections[$sConnName];
	}

	/**
	 * Returns connection name
	 *
	 * @param	string|null	$sName	connection name or NULL for default one
	 * @throws	Exception
	 * @return	string
	 */
	protected function getConnName($sName = null)
	{
		// got connection name
		if($sName !== null)
		{
			return $sName;
		}

		// no default connection
		if(empty($this->sDefaultConn))
		{
			throw new Exception('There is no active connections');
		}

		return $this->sDefaultConn;
	}

	/**
	 * Returns DB name
	 *
	 * @param	string|null	$sName	database name or NULL for default one
	 * @throws	Exception
	 * @return	string
	 */
	protected function getDbName($sName = null)
	{
		// got database name
		if($sName !== null)
		{
			return $sName;
		}

		// no default database
		if(empty($this->sDefaultDb))
		{
			throw new Exception('There is no default database');
		}

		return $this->sDefaultDb;
	}
}
