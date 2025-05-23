<?php

namespace isMdl;

use Exception;
use mathml;

class Mnsequation extends MdbTable {
    
    function __construct(string $tablename) {
        parent::__construct($tablename);
    }

    /**
     * Override MdbTable::set to transparently unserialize 'parsetree', 'normalized', 'expanded', 'lineqstd'
     * 
     * @param string $name 
     * @return mixed 
     * @throws Exception 
     */
    public function get(string $name) {
        $value = parent::get($name);
        if (in_array($name, ['parsetree', 'normalized', 'expanded', 'lineqstd'])) {
            if ($value !== null) {
                $value = unserialize($value);
            }
        }
        return $value;
    }

    /**
     * Override MdbTable::set to transparently serialize 'parsetree', 'normalized', 'expanded', 'lineqstd'
     * 
     * @param string $name 
     * @param mixed $value 
     * @return void 
     * @throws Exception 
     */
    public function set(string $name, $value) {
        if (in_array($name, ['parsetree', 'normalized', 'expanded', 'lineqstd'])) {
            if ($value !== null) {
                $value = serialize($value);
            }
        }
        parent::set($name, $value);
    }

}