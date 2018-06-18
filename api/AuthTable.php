<?php

/**
 * @Deprecated
 */
class AuthTable extends xmldb_table
{

    /**
     * @Deprecated
     * AuthTable constructor.
     */
    public function __construct()
    {
        parent::__construct('auth');

        $this->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $this->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $this->add_field('accessdomain', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $this->add_field('token', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $this->add_field('accesseddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $this->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $this->add_key('useraccessdomain', XMLDB_KEY_UNIQUE, array('userid', 'accessdomain'));
        $this->add_key('token', XMLDB_KEY_UNIQUE, array('token'));
        $this->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
    }
}

