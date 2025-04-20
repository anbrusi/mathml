<?php

namespace isMdl;

class MmodelBase {

    /**
     * The name of the table, where the modelled data is stored
     * 
     * @var string
     */
    protected string $table;

    protected int|string|null $id = null;
    
    function __construct(string $tablename) {
        $this->table = $tablename;
    }

    function exists(int|string|null $id):bool {
        if ($id === null) {
            return false;
        }
        $stmt = \isLib\Ldb::prepare('SELECT id FROM '.$this->table.' WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetchColumn();
        if ($r === false) {
            return false;
        } else {
            return true;
        }
    }
}