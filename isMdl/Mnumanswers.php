<?php

namespace isMdl;

class Mnumanswers extends MmodelBase {

    private int $user;

    private int $questionid;

    private string $answer = '';

    function __construct(string $tablename)
    {
        parent::__construct($tablename);
    }

    public function load(int $id): bool
    {
        if ($this->exists($id)) {
            $sql = 'SELECT user, questionid, answer FROM Tnumanswers WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $this->id = $id;
            $this->user = $row['user'];
            $this->answer = $row['answer'];
            return true;
        } else {
            return false;
        }
    }

    public function store(int $questionid, int $user, string $answer):int|false {
        $this->user = $user;
        $this->questionid = $questionid;
        $this->answer = $answer;
        $sql = 'INSERT INTO Tnumanswers(questionid, user, answewr) VALUES(:questionid, :user, :answer)';
        $stmt = \isLib\Ldb::prepare($sql);
        if ($stmt->execute(['questionid' => $this->questionid, 'user' => $this->user, 'answer' => $this->answer])) {
            $this->id = \isLib\Ldb::lastInsertId();
            return $this->id;
        } else {
            // We do not remove the question, although it is not consistent.
            return false;
        }
    }

    public function update(int $id, int $user, int $questionid, string $answer):bool {
        $loaded = $this->load($id);
        if ($loaded) {
            $this->user = $user;
            $this->questionid = $questionid;
            $this->answer=$answer;
            // Update
            $sql = 'UPDATE Tnumanswers SET user=:user, questionid=:questionid, answer=:answer WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            if ($stmt->execute([
                'user'=> $this->user,
                'questionid' => $this->questionid,
                'answer' => $this->answer,
                'id' => $id
            ])) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception('Numeric answer '.$id,' was not found');
        }
    }

}