<?php

namespace isMdl;

use mathml;

class Mnsequation extends MmodelBase {
    
    private int $user;

    private int $questionid;

    private string $mathml = '';

    private int $sourceoffset;

    private array|null $parsetree = null;

    private array|null $normalized = null;

    private array|null $expanded = null;

    private array|null $lineqstd = null;

    function __construct(string $tablename) {
        parent::__construct($tablename);
    }

    public function load(int $id):bool {
        if ($this->exists($id)) {
            $sql = 'SELECT user, questionid, mathml, sourceoffset, parsetree, normalized, expanded, lineqstd FROM Tnsequations WHERE id=:id';
            $stmt = \isLib\ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $this->id = $id;
            $this->user = $row['user'];
            $this->questionid = $row['questionid'];
            $this->mathml = $row['mathml'];
            $this->sourceoffset = $row['sourceoffset'];
            if ($row['parsetree'] === null) {
                $this->parsetree = null;
            } else {
                $parsetree = unserialize($row['parsetree']);
                if ($parsetree === false) {
                    $this->parsetree = null;
                } else {
                    $this->parsetree = $parsetree;
                }
            }
            if ($row['normalized'] === null) {
                $this->normalized = null;
            } else {
                $normalized = unserialize($row['normalized']);
                if ($normalized === false) {
                    $this->normalized = null;
                } else {
                    $this->normalized = $normalized;
                }
            }
            if ($row['expanded'] === null) {
                $this->expanded = null;
            } else {
                $expanded = unserialize($row['expanded']);
                if ($expanded === false) {
                    $this->expanded = null;
                } else {
                    $this->expanded = $expanded;
                }
            }
            if ($row['lineqstd'] === null) {
                $this->lineqstd = null;
            } else {
                $lineqstd = unserialize($row['lineqstd']);
                if ($lineqstd === false) {
                    $this->lineqstd = null;
                } else {
                    $this->lineqstd = $lineqstd;
                }
            }
            return true;
        } else {
            return false;
        }
    }


    public function store(bool $new = false):int {
        $exists = $this->exists($this->id);
        $parsetree = serialize($this->parsetree);
        $normalized = serialize($this->normalized);
        $expanded = serialize($this->expanded);
        $lineqstd = serialize($this->lineqstd);
        if ($exists && !$new) {
            // Update
            $sql = 'UPDATE Tnsequations SET user=:user, questionid=:questionid, mathml=:mathml, sourceoffset=:sourceoffset, '.
                   'parsetree=:parsetree, normalized=:normalized, expanded=:expanded, lineqstd=:lineqstd WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'questionid' => $this->questionid, 'mathml' => $this->mathml, 'sourceoffset' => $this->sourceoffset,
                            'parsetree' => $parsetree, 'normalized' => $normalized, 'expanded' => $expanded, 'lineqstd' => $lineqstd, 'id' => $this->id]);
            return $this->id;
        } else {
            // store new
            $sql = 'INSERT INTO Tnsequations(user, questionid, mathml, sourceoffset, parsetree, normalized, expanded, lineqstd) '.
                   'VALUES(:user, :questionid, :mathml, :sourceoffset, :parsetree, :normalized, :expanded, :lineqstd)';
            $stmt = \isLib\ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'questionid' => $this->questionid, 'mathml' => $this->mathml, 'sourceoffset' => $this->sourceoffset,
                            'parsetree' => $parsetree, 'normalized' => $normalized, 'expanded' => $expanded, 'lineqstd' => $lineqstd]);
            $sql = 'UPDATE Tnsequations SET id=:id';
            $id = \isLib\Ldb::lastInsertId();
            $this->id = $id;
            return $id;

        }
    }

    public function getUser():int {
        return $this->user;
    }

    public function setUser(int $user):void {
        $this->user = $user;
    }

    public function getQuestionid():int {
        return $this->questionid;
    }

    public function setQuestionid(int $questionid):void {
        $this->questionid = $questionid;
    }

    public function getMathml():string {
        return $this->mathml;
    }

    public function setMathml(string $mathml):void {
        $this->mathml = $mathml;
    }

    public function getSourceoffset():int {
        return $this->sourceoffset;
    }

    public function setSourceoffset(int $sourceoffset):void {
        $this->sourceoffset = $sourceoffset;
    }

    public function getParsetree():array|null {
        return $this->parsetree;
    }

    public function setParsetree(array|null $parsetree):void {
        $this->parsetree = $parsetree;
    }

    public function getNormalized():array|null {
        return $this->normalized;
    }

    public function setNormalized(array|null $normalized):void {
        $this->normalized = $normalized;
    }

    public function getExpanded():array|null {
        return $this->expanded;
    }

    public function setExpanded(array|null $expanded):void {
        $this->expanded = $expanded;
    }

    public function getLineqstd():array|null {
        return $this->lineqstd;
    }

    public function setLineqstd(array|null $lineqstd):void {
        $this->lineqstd = $lineqstd;
    }
}