<?php namespace Config\Loader\Ldap;

use Config\Loader\Accessor;

class LdapAccessor extends Accessor
{
    public function get($key)
    {
        return $this->item->getAttribute($key);
    }

    public function has($key)
    {
        return $this->item->hasAttribute($key);
    }
}