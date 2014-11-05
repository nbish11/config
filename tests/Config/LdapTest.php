<?php

use Config\Repository;
use Config\Loader\LdapLoader;


class RepositoryLdapTest extends \PHPUnit_Framework_TestCase {

    protected $ldapManager;

    protected $ldapLoader;

    public function setUp()
    {
        $this->ldapManager = $this->getMock('Config\Util\Ldap\Connection', array(), array('localhost'));

        //$this->ldapManager->connect();
        //$this->ldapManager->bind();

        $this->ldapLoader = new LdapLoader($this->ldapManager, array(
            ''          => 'ou=Master,dc=example,dc=com',
            'staging'   => 'ou=Staging,dc=example,dc=com',
        ));
    }

    public function testRepositoryInitialises(){

        $repo = new Repository($this->ldapLoader);

        $entry = $this->getMock('Config\Util\Ldap\API\EntryInterface');

        $entry->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue(array('foo', 'bar')));

        $this->ldapManager->expects($this->once())
            ->method('getEntry')
            ->with('cn=test,ou=Master,dc=example,dc=com')
            ->will($this->returnValue($entry));

        $actual = $repo->get('test');

        $this->assertEquals(array('foo', 'bar'), $actual);
    }

    public function testRepositoryInitialisesWithEnv(){

        $repo = new Repository($this->ldapLoader, 'staging.test');

        $entry = $this->getMock('Config\Util\Ldap\API\EntryInterface');

        $entry->expects($this->at(0))
            ->method('getAttributes')
            ->will($this->returnValue(array('foo' => false, 'bar' => 'baz')));

        $entry->expects($this->at(1))
            ->method('getAttributes')
            ->will($this->returnValue(array('foo' => 'boo')));

        $this->ldapManager->expects($this->at(0))
            ->method('getEntry')
            ->with('cn=test,ou=Master,dc=example,dc=com')
            ->will($this->returnValue($entry));

        $this->ldapManager->expects($this->at(1))
            ->method('getEntry')
            ->with('cn=test,ou=Staging,dc=example,dc=com')
            ->will($this->returnValue($entry));

        $actual = $repo->get('test');

        $this->assertEquals(array('foo' =>'boo', 'bar' => 'baz'), $actual);
    }

    public function testNodeNotFoundInitialises(){

        $repo = new Repository($this->ldapLoader);

        $this->ldapManager->expects($this->once())
            ->method('getEntry')
            ->with('cn=foobar,ou=Master,dc=example,dc=com')
            ->will($this->throwException(new \Config\Util\Ldap\Exception\NodeNotFoundException()));

        $this->assertNull($repo->get('foobar'));
    }

}