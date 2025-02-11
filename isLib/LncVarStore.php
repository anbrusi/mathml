<?php

namespace isLib;

/**
 * Implements a persistent store for nanoCAS variables, i.e. named nanoCas objects
 * Must be implemented depending on the needs of the application using nanoCas.
 * 
 * @package isLib
 */
class LncVarStore {

    /**
     * Stores new variable $name or overwrites existing variable $name
     * 
     * @param string $name 
     * @param array $value 
     */
    public function storeVar(string $name, array $value) {
        \isLib\LinstanceStore::setNCvariable($name, $value);
    }

    /**
     * Returns the variable $name as nanoCAS variable or null if it does not exist
     * 
     * @param string $name 
     * @return array|null 
     */
    public function getVar(string $name):array|null {
        return \isLib\LinstanceStore::getNCvariable($name);
    }

    public function listVariables():array {
        if (isset($_SESSION['ncvars'])) {
            return $_SESSION['ncvars'];
        } else {
            return [];
        }
    }
}