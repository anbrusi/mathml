<?php

namespace isMdl;

class Mnumquestion extends MmodelBase {

    private int $user;

    private string $name = '';

    private string $question = '';

    private string $solution = '';

    function __construct(string $tablename) {
        parent::__construct($tablename);
    }

    public function load(int $id):bool {
        if ($this->exists($id)) {
            $sql = 'SELECT user, name, question, solution FROM Tnumquestions WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $this->id = $id;
            $this->user = $row['user'];
            $this->name = $row['name'];
            $this->question = $row['question'];
            $this->solution = $row['solution']; 
            return true;
        } else {
            return false;
        }
    }

    /**
     * If the question does not exist a new DB entry is created and the id is returned
     * If the question exists and $new === false (the default) the existing question is overwritten,
     * if $new === true, the question is stored with a new id
     * 
     * @param bool $new 
     * @return int 
     */
    public function store(bool $new = false):int {
        $exists = $this->exists($this->id);
        if ($exists && !$new) {
            // Update
            $sql = 'UPDATE Tnumquestions SET user=:user, name=:name, question=:question, solution=:solution WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'name' => $this->name, 'question' => $this->question, 'solution' => $this->solution, 'id' => $this->id]);
            return $this->id;
        } else {
            // Store new
            $sql = 'INSERT INTO Tnumquestions(user, name, question, solution) VALUES(:user, :name, :question, :solution)';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'name' => $this->name, 'question' => $this->question, 'solution' => $this->solution]);
            $sql = 'UPDATE Tnumquestions SET id=:id';
            $id = \isLib\Ldb::lastInsertId();
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            return $id;
        }
    }

    public function getUser():int {
        return $this->user;
    }

    public function setUser(int $user):void {
        $this->user = $user;
    }

    public function getName():string {
        return $this->name;
    }

    public function setName(string $name):void {
        $this->name = $name;
    }

    public function getQuestion():string {
        return $this->question;
    }

    public function setQuestion(string $question):void {
        $this->question = $question;
    }

    public function getSolution():string {
        return $this->solution;
    }

    public function setSolution(string $solution):void {
        $this->solution = $solution;
    }

}