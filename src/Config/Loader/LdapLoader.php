<?php namespace Config\Loader;

use Toyota\Component\Ldap\Core\Manager;

class LdapLoader extends LoaderAbstract
{
    /**
     * @var Manager
     */
    protected $ldapConnection;

    /**
     * @var null
     */
    protected $baseDn;


    /**
     * Create a new file configuration loader.
     *
     * @param Manager $ldapConnection
     * @param null $baseDn
     */
    public function __construct(Manager $ldapConnection, $baseDn = null)
    {
        $this->ldapConnection = $ldapConnection;

        $this->baseDn = $baseDn;
    }

    /**
     * @param array $pathParts
     * @param $group
     * @return mixed
     */
    protected function readConfig(array $pathParts, $group)
    {
        $dn = $this->buildDn(array_reverse($pathParts), $group);

        return new Ldap\Accessor($this->ldapConnection->getNode($dn));
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
        $subDn = implode(",dc=", $dnParts);

        $parts = array_filter(array(
            "cn=$group",
            $subDn,
            $this->baseDn
        ));

        return implode(',', $parts);
    }

}
