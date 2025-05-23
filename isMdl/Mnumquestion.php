<?php

namespace isMdl;

use PDOException;
use Exception;

class Mnumquestion extends Mnumsolution {

    private string $name = '';

    private string $question = '';

  
    function __construct(string $tablename)
    {
        parent::__construct($tablename);
    }

	/**
	 * Overrides get to transparently unserialize 'matrix' and 'varvalues'
	 *
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function get(string $name) {
        $value = parent::get($name);
		if ($name == 'matrix' || $name == 'varvalues') {
			if ($value !== null) {
				$value = unserialize($value);
			}
		} 
		return $value;
	}

    public function getNsequations():array {
        return $this->nsequations; // This is a property of Mnumsolution
    }

	/**
	 * Override MdbTable::set to transparently serialize 'params'
	 *
	 * {@inheritDoc}
	 * @see \isMdl\MdbTable::set()
	 */
	public function set(string $name, $value) {
		if ($name == 'matrix' || $name == 'varvalues') {
			if ($value !== null) {
                $value = serialize($value);
			}
		}
		parent::set($name, $value);
	}

    /**
     * Override MdbTable::load, to load nsequations, which reside in an own table
     * 
     * @param mixed $id 
     * @return void 
     * @throws PDOException 
     * @throws Exception 
     */
    public function load($id) {
        parent::load($id);
        $this->loadNsequations($id);
        $this->processSolution($this->get('solution'), $this->get('user'), $id);
    }


    private function loadNsequations(int $questionid): void
    {
        $sql = 'SELECT id FROM Tnsequations WHERE questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['questionid' => $questionid]);
        while ($row = $stmt->fetch()) {
            $Mnsequation = new \isMdl\Mnsequation('Tnsequations');
            $Mnsequation->load($row['id']);
            $this->nsequations[] = $Mnsequation;
        }
    }

    /**
     * Creates an object of class Mnumquestion and stores its representation in the DB, returning the id in 'Tnumquestion'
     * 
     * @param int $user 
     * @param string $name 
     * @param string $question 
     * @param string $solution 
     * @return int 
     * @throws PDOException 
     */
    /*
    public function store(int $user, string $name, string $question, string $solution):int|false {
        $this->user = $user;
        $this->name = $name;
        $this->question = $question;
        $this->solution = $solution;
        $sql = 'INSERT INTO Tnumquestions(user, name, question, solution) VALUES(:user, :name, :question, :solution)';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['user' => $this->user, 'name' => $this->name, 'question' => $this->question, 'solution' => $this->solution]);
        $this->id = \isLib\Ldb::lastInsertId();
        if ($this->processSolution()) {  // Makes use of $this->id
            // Store everything except the id in 'Tnumquestions'
            if ($this->matrix !== null) {
                $matrix = serialize($this->matrix);
            } else {
                $matrix = null;
            }
            if ($this->varvalues !== null) {
                $varvalues = serialize($this->varvalues);
            } else {
                $varvalues = null;
            }
            // Update 'Tnumquestions', to store the missing fields
            $sql = 'UPDATE Tnumquestions SET id=:id, matrix=:matrix, varvalues=:varvalues';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $this->id, 'matrix' => $matrix, 'varvalues' => $varvalues]);
            // Store nsequations, which have been built by process solution
            $this->storeNsequations();
            return $this->id;
        } else {
            // We do not remove the question, although it is not consistent.
            return false;
        }
    }
        */

    public function insert($primaryKey=NULL) {
        $id = parent::insert();
        $this->storeNsequations($id);
        $this->processSolution($this->get('solution'), $this->get('user'), $this->get('id'));
        return $id;
    }

    public function update() {
        parent::update();
        $this->storeNsequations($this->get('id'));
        $this->processSolution($this->get('solution'), $this->get('user'), $this->get('id'));
    }

    /**
     * Deletes old entries in Tnsequations, and stores the new values.
     * 
     * @return void 
     */
    private function storeNsequations(int $questionid): void
    {
        // Remove all previously present equations
        $sql = 'DELETE FROM Tnsequations WHERE questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['questionid' => $questionid]);
        foreach ($this->nsequations as $nsequation) {
            $nsequation->insert();
        }
    }
}
