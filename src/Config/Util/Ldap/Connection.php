<?php namespace Config\Util\Ldap;

/*
 * This file is part of the Toyota Legacy PHP framework package.
 *
 * (c) Toyota Industrial Equipment <cyril.cottet@toyota-industries.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Config\Util\Ldap\Exception\ConnectionException;
use Config\Util\Ldap\Exception\OptionException;
use Config\Util\Ldap\Exception\BindException;
use Config\Util\Ldap\Exception\PersistenceException;
use Config\Util\Ldap\Exception\NoResultException;
use Config\Util\Ldap\Exception\SizeLimitException;
use Config\Util\Ldap\Exception\MalformedFilterException;
use Config\Util\Ldap\Exception\SearchException;
use Config\Util\Ldap\Exception\NodeNotFoundException;
use Config\Util\Ldap\Exception\NotBoundException;

use Config\Util\Ldap\API\SearchInterface;
use Config\Util\Ldap\API\ConnectionInterface;

/**
 * Connection implementing interface for php ldap native extension
 *
 * @author Cyril Cottet <cyril.cottet@toyota-industries.eu>
 */
class Connection implements ConnectionInterface
{

    const SECURITY_TLS = 'tls';

    const SECURITY_SSL = 'ssl';

    /**
     * @var
     */
    protected $connection;

    /**
     * @var
     */
    protected $hostname;

    /**
     * @var
     */
    protected $port;

    /**
     * @var
     */
    protected $security;

    /**
     * @var bool
     */
    protected $isBound = false;


    public function __construct($hostname, $port = null, $security = null)
    {
        if (! extension_loaded('ldap'))
        {
            throw new ConnectionException('You do not have the required ldap-extension installed');
        }

        $this->hostname = $hostname;

        if (is_null($port))
        {
            $port = $security === self::SECURITY_SSL ? 636 : 389;
        }

        $this->port = $port;

        $this->security = $security;
    }

    /**
     * Connects to a Ldap directory without binding
     *
     * @throws ConnectionException
     */
    public function connect() {

        $hostname = $this->hostname;

        if ($this->security === self::SECURITY_SSL) {
            $hostname = 'ldaps://' . $hostname;
        }

        $connection = @ldap_connect($hostname, $this->port);
        if (false === $connection) {
            throw new ConnectionException('Could not successfully connect to the LDAP server');
        }

        if ($this->security === self::SECURITY_TLS) {
            if (! (@ldap_start_tls($connection))) {
                $code = @ldap_errno($connection);
                throw new ConnectionException(
                    sprintf('Could not start TLS: Ldap Error Code=%s - %s', $code, ldap_err2str($code))
                );
            }
        }

        $this->connection = $connection;
    }

    /**
     * Set an option
     *
     * @param int   $option Ldap option name
     * @param mixed $value  Value to set on Ldap option
     *
     * @return void
     *
     * @throws OptionException if option cannot be set
     */
    public function setOption($option, $value)
    {
        if (! (@ldap_set_option($this->connection, $option, $value))) {
            $code = @ldap_errno($this->connection);
            throw new OptionException(
                sprintf(
                    'Could not change option %s value: Ldap Error Code=%s - %s',
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Gets current value set for an option
     *
     * @param int $option Ldap option name
     *
     * @return mixed value set for the option
     *
     * @throws OptionException if option cannot be retrieved
     */
    public function getOption($option)
    {
        $value = null;
        if (! (@ldap_get_option($this->connection, $option, $value))) {
            $code = @ldap_errno($this->connection);
            throw new OptionException(
                sprintf(
                    'Could not retrieve option %s value: Ldap Error Code=%s - %s',
                    $code,
                    ldap_err2str($code)
                )
            );
        }
        return $value;
    }

    /**
     * Binds to the LDAP directory with specified RDN and password
     *
     * @param string   $rdn        Rdn to use for binding (Default: null)
     * @param string   $password   Plain or hashed password for binding (Default: null)
     *
     * @return void
     *
     * @throws BindException if binding fails
     */
    public function bind($rdn = null, $password = null)
    {
        $isAnonymous = false;
        if ((null === $rdn) || (null === $password)) {
            if ((null !== $rdn) || (null !== $password)) {
                throw new BindException(
                    'For an anonymous binding, both rdn & passwords have to be null'
                );
            }
            $isAnonymous = true;
        }

        if (! (@ldap_bind($this->connection, $rdn, $password))) {
            $code = @ldap_errno($this->connection);
            throw new BindException(
                sprintf(
                    'Could not bind %s user: Ldap Error Code=%s - %s',
                    $isAnonymous?'anonymous':'privileged',
                    $code,
                    ldap_err2str($code)
                )
            );
        }

        $this->isBound = true;
    }


    /**
     * Closes the connection
     *
     * @return void
     *
     * @throws ConnectionException if connection could not be closed
     */
    public function close()
    {
        $this->validateBinding();

        if (! (@ldap_unbind($this->connection))) {
            $code = @ldap_errno($this->connection);
            throw new ConnectionException(
                sprintf(
                    'Could not close the connection: Ldap Error Code=%s - %s',
                    $code,
                    ldap_err2str($code)
                )
            );
        }

        $this->connection = null;
    }

    /**
     * Adds a Ldap entry
     *
     * @param string $dn   Distinguished name to register entry for
     * @param array  $data Ldap attributes to save along with the entry
     *
     * @return void
     *
     * @throws PersistenceException if entry could not be added
     */
    public function addEntry($dn, $data)
    {
        $this->validateBinding();

        $data = $this->normalizeData($data);

        if (! (@ldap_add($this->connection, $dn, $data))) {
            $code = @ldap_errno($this->connection);
            throw new PersistenceException(
                sprintf(
                    'Could not add entry %s: Ldap Error Code=%s - %s',
                    $dn,
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Deletes an existing Ldap entry
     *
     * @param string $dn Distinguished name of the entry to delete
     *
     * @return void
     *
     * @throws PersistenceException if entry could not be deleted
     */
    public function deleteEntry($dn)
    {
        $this->validateBinding();

        if (! (@ldap_delete($this->connection, $dn))) {
            $code = @ldap_errno($this->connection);
            throw new PersistenceException(
                sprintf(
                    'Could not delete entry %s: Ldap Error Code=%s - %s',
                    $dn,
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Adds some value(s) to some entry attribute(s)
     *
     * The data format for attributes is as follows:
     *     array(
     *         'attribute_1' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *         'attribute_2' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *          ...
     *     );
     *
     * @param string $dn   Distinguished name of the entry to modify
     * @param array  $data Values to be added for each attribute
     *
     * @return void
     *
     * @throws PersistenceException if entry could not be updated
     */
    public function addAttributeValues($dn, $data)
    {
        $this->validateBinding();

        $data = $this->normalizeData($data);

        if (! (@ldap_mod_add($this->connection, $dn, $data))) {
            $code = @ldap_errno($this->connection);
            throw new PersistenceException(
                sprintf(
                    'Could not add attribute values for entry %s: Ldap Error Code=%s - %s',
                    $dn,
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Replaces value(s) for some entry attribute(s)
     *
     * The data format for attributes is as follows:
     *     array(
     *         'attribute_1' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *         'attribute_2' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *          ...
     *     );
     *
     * @param string $dn   Distinguished name of the entry to modify
     * @param array  $data Values to be set for each attribute
     *
     * @return void
     *
     * @throws PersistenceException if entry could not be updated
     */
    public function replaceAttributeValues($dn, $data)
    {
        $this->validateBinding();

        $data = $this->normalizeData($data);

        if (! (@ldap_mod_replace($this->connection, $dn, $data))) {
            $code = @ldap_errno($this->connection);
            throw new PersistenceException(
                sprintf(
                    'Could not replace attribute values for entry %s: Ldap Error Code=%s - %s',
                    $dn,
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Delete value(s) for some entry attribute(s)
     *
     * The data format for attributes is as follows:
     *     array(
     *         'attribute_1' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *         'attribute_2' => array(
     *             'value_1',
     *             'value_2'
     *          ),
     *          ...
     *     );
     *
     * @param string $dn   Distinguished name of the entry to modify
     * @param array  $data Values to be removed for each attribute
     *
     * @return void
     *
     * @throws PersistenceException if entry could not be updated
     */
    public function deleteAttributeValues($dn, $data)
    {
        $this->validateBinding();

        $data = $this->normalizeData($data);

        if (! (@ldap_mod_del($this->connection, $dn, $data))) {
            $code = @ldap_errno($this->connection);
            throw new PersistenceException(
                sprintf(
                    'Could not delete attribute values for entry %s: Ldap Error Code=%s - %s',
                    $dn,
                    $code,
                    ldap_err2str($code)
                )
            );
        }
    }

    /**
     * Searches for entries in the directory
     *
     * @param int $baseDn Base distinguished name to look below
     * @param string $filter Filter for the search
     * @param null $attributes Names of attributes to retrieve (Default: All)
     * @param null $scope Search scope (ALL, ONE or BASE)
     * @return SearchInterface|Search
     * @throws MalformedFilterException
     * @throws NoResultException
     * @throws NotBoundException
     * @throws SearchException
     * @throws SizeLimitException
     */
    public function search($baseDn, $filter, $attributes = null, $scope = null)
    {
        $this->validateBinding();

        switch ($scope) {
            case null:
            case SearchInterface::SCOPE_BASE:
                $function = 'ldap_read';
                break;
            case SearchInterface::SCOPE_ONE:
                $function = 'ldap_list';
                break;
            case SearchInterface::SCOPE_ALL:
                $function = 'ldap_search';
                break;
            default:
                throw new SearchException(sprintf('Scope %s not supported', $scope));
        }

        $params = array($this->connection, $baseDn, $filter);
        if (is_array($attributes)) {
            $params[] = $attributes;
        }

        if (false === ($search = @call_user_func_array($function, $params))) {
            $code = @ldap_errno($this->connection);
            switch ($code) {

            case 32:
                throw new NoResultException('No result retrieved for the given search');
                break;
            case 4:
                throw new SizeLimitException(
                    'Size limit reached while performing the expected search'
                );
                break;
            case 87:
                throw new MalformedFilterException(
                    sprintf('Search for filter %s fails for a malformed filter', $filter)
                );
                break;
            default:
                throw new SearchException(
                    sprintf(
                        'Search on %s with filter %s failed. Ldap Error Code:%s - %s',
                        $baseDn,
                        $filter,
                        $code,
                        ldap_err2str($code)
                    )
                );
            }
        }

        return new Search($this->connection, $search);
    }

    /**
     * Normalizes data for Ldap storage
     *
     * @param array $data Ldap data to store
     *
     * @return array Normalized data
     */
    protected function normalizeData($data)
    {
        foreach ($data as $attribute => $info) {
            if (is_array($info)) {
                if (count($info) == 1) {
                    $data[$attribute] = $info[0];
                    continue;
                }
            }
        }
        return $data;
    }

    /**
     * Validates that Ldap is bound before performing some kind of operation
     *
     * @return void
     *
     * @throws NotBoundException if binding has not occured yet
     */
    protected function validateBinding()
    {
        if (! $this->isBound) {
            throw new NotBoundException('You have to bind to the Ldap first');
        }
    }

    /**
     * Retrieve a single entry knowing its dn
     *
     * @param string $dn         Distinguished name of the node to look for
     * @param array  $attributes Filter attributes to be retrieved (Optional)
     * @param string $filter     Ldap filter according to RFC4515 (Optional)
     *
     * @return Entry
     *
     * @throws NodeNotFoundException if node cannot be retrieved
     */
    public function getEntry($dn, $attributes = null, $filter = '(objectclass=*)')
    {
        try {
            $search = $this->search(
                $dn,
                $filter,
                $attributes,
                SearchInterface::SCOPE_BASE
            );
        } catch (NoResultException $e) {
            throw new NodeNotFoundException(sprintf('Node %s not found', $dn));
        }

        if (null === ($entry = $search->next())) {
            throw new NodeNotFoundException(sprintf('Node %s not found', $dn));
        }

        return $entry;
    }
}