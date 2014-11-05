<?php namespace Config\Loader;

use Config\Util\Ldap\Connection;
use Config\Util\Ldap\Exception\NodeNotFoundException;

class LdapLoader extends LoaderAbstract
{
    /**
     * @var Manager
     */
    protected $ldapConnection;

    /**
     * @var null
     */
    protected $environmentDns;


    /**
     * Create a new file configuration loader.
     *
     * @param Connection $ldapConnection
     * @param array $environmentDns
     */
    public function __construct(Connection $ldapConnection, array $environmentDns)
    {
        $this->ldapConnection = $ldapConnection;

        $this->environmentDns = $environmentDns;
    }

    /**
     * @param array $pathParts
     * @param $group
     * @return mixed
     */
    protected function readConfig(array $pathParts, $group)
    {
        $dn = $this->buildDn($pathParts, $group);

        return $dn ? $this->readNodeValues($dn) : null;
    }

    /**
     * @param $dn
     * @return array
     */
    protected function readNodeValues($dn)
    {
        try
        {
            return $this->ldapConnection->getEntry($dn)->getAttributes();

        }catch(NodeNotFoundException $e) {}
    }

    /**
     * Read the file and parse it returning the read array
     *
     * @param array $dnParts
     * @param $group
     * @return string
     */
    protected function buildDn(array $dnParts, $group)
    {
        $envDn = implode('.', array_filter($dnParts));

        if(isset($this->environmentDns[$envDn]))
        {
            return "cn=$group," . $this->environmentDns[$envDn];
        }
    }

}
