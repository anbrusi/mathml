<?php

namespace isMdl;

use isLib\Lgauss;

class Mnumquestion extends MmodelBase {

    private int $user;

    private string $name = '';

    private string $question = '';

    private string $solution = '';

    /**
     * The original matrix built from lineqstd in $this->nsquestions
     * Position 0 is the matrix proper, position 1 a vector with the names of the variables ('1' for the constants)
     * 
     * @var array|null
     */
    private array|null $matrix = null; 

    private array|null $varvalues = null;

    /**
     * Numeric array of offsets of mathML expressions, which cannot be decoded
     *  
     * @var array
     */
    private array $undecodableMathlOffsets = [];

    /**
     * Numeric array of offsets of mathML expressions, which are not equations
     * 
     * @var array
     */
    private $spuriousMathmlOffsets = []; 

    /**
     * Numeric array of Mnsequation
     * 
     * @var array
     */
    private array $nsequations = [];

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
            $this->loadNsequations();
            return true;
        } else {
            return false;
        }
    }

    private function loadNsequations():void {
        $sql = 'SELECT id FROM Tnsequations WHERE questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['questionid' => $this->id]);
        while ($row = $stmt->fetch()) {
            $Mnsequation = new \isMdl\Mnsequation('Tnsequations');
            if ($Mnsequation->load($row['id'])) {
                $this->nsequations[] = $Mnsequation;
            }
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
        $exists = $this->exists($this->id);
        if ($exists && !$new) {
            // Update
            $sql = 'UPDATE Tnumquestions SET user=:user, name=:name, question=:question, solution=:solution, matrix=:matrix, varvalues=:varvalues WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'name' => $this->name, 'question' => $this->question, 'solution' => $this->solution, 
                            'matrix' => $matrix, 'varvalues' => $varvalues, 'id' => $this->id]);
            $this->storeNsequations();
            return $this->id;
        } else {
            // Store new
            $sql = 'INSERT INTO Tnumquestions(user, name, question, solution, matrix, varvalues) VALUES(:user, :name, :question, :solution, :matrix, :varvalues)';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['user' => $this->user, 'name' => $this->name, 'question' => $this->question, 'solution' => $this->solution, 'matrix' => $matrix, 'varvalues' => $varvalues]);
            $sql = 'UPDATE Tnumquestions SET id=:id';
            $id = \isLib\Ldb::lastInsertId();
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            $this->storeNsequations();
            return $id;
        }
    }

    /**
     * Elements is an array of elements, whith an offset (int) in position 1
     * getFromOffset returns the first element, which has $offset in position 1
     * If none is found false is returned
     * 
     * @param array $elements 
     * @return mixed 
     */
    private function getFromOffset(array $elements, int $offset):mixed {
        foreach ($elements as $element) {
            if ($element[1] == $offset) {
                return $element;
            }
        }
        return false;
    }

    /**
     * Rebuilds $this->nsequationsand $this->matrix from $this->solution
     * 
     * @return void 
     */
    public function processSolution():void {
        $mathmlExpressions = \isLib\Ltools::getMathmlExpressions($this->solution);
        // Detect expressions, which cannot be decoded
        $asciiExpressions = [];
        $this->undecodableMathlOffsets = [];
        $LpresentationParser = new \isLib\LpresentationParser();
        foreach ($mathmlExpressions as $mathmlExpression) {
            try {
                $asciiExpression = $LpresentationParser->getAsciiOutput($mathmlExpression[0]);
                $offset = $mathmlExpression[1];
                $asciiExpressions[] = [$asciiExpression, $offset];
            } catch (\Exception $ex) {
                $this->undecodableMathlOffsets[] = $mathmlExpression[1];
            }
        }
        // At this stage all decodable math is in $asciiExpressions. Erroneous mathML in $this->undecodableMathml
        $equations = [];
        foreach ($asciiExpressions as $asciiExpression) {
            $expression = $asciiExpression[0];
            $offset = $asciiExpression[1];
            $parts = explode('=', $expression);
            if (count($parts) == 2) {
                $equation = $parts[0].'-('.$parts[1].')';
                $equations[] = [$equation, $offset];
            } else {
                $this->spuriousMathmlOffsets[] = $asciiExpression[1];
            }
        }
        // At this stage all equations are in $equations as an ascii expression, that must be equal to zero.
        $this->nsequations = [];
        $nrEquations = count($equations);
        for ($i = 0; $i < $nrEquations; $i++) {   
            $nsequation = new \isMdl\Mnsequation('Tnsequations');
            $nsequation->setUser($this->user);
            $nsequation->setQuestionid($this->id);
            $offset = $equations[$i][1];
            $mathml = $this->getFromOffset($mathmlExpressions, $offset);
            if ($mathml === false) {
                // Could not find searched offset
                \isLib\LmathError::setError(\isLib\LmathError::ORI_MNUMQUESTION, 1);
            }
            $nsequation->setMathml($mathml[0]);
            $nsequation->setSourceoffset($offset);  
            try {      
                $LasciiParser = new \isLib\LasciiParser($equations[$i][0]);
                $LasciiParser->init();
                $parseTree = $LasciiParser->parse(); 
                $nsequation->setParsetree($parseTree);
            } catch (\Exception $ex) {
                $nsequation->setParsetree(null);
            }
            $this->nsequations[] = $nsequation;
        }
        // At this stage $this->nsequations has been remade, but only properties up to 'parsetree' are set
        // Equations whose ascii expression could not be parsed have property 'parsetree' equal to null
        $LtreeTrf = new \isLib\LtreeTrf(\isLib\Lconfig::CF_TRIG_UNIT);
        for ($i = 0; $i < $nrEquations; $i++) {
            if ($this->nsequations[$i]->getParsetree() !== null) {
                try {
                    $normalized = $LtreeTrf->normalize($this->nsequations[$i]->getParseTree());
                    $this->nsequations[$i]->setNormalized($normalized);
                } catch(\Exception $ex) {
                    // Do nothing, the default already is null
                }
            }
        }
        for ($i = 0; $i < $nrEquations; $i++) {
            if ($this->nsequations[$i]->getNormalized() !== null) {
                try {
                    $expanded = $LtreeTrf->partEvaluate($this->nsequations[$i]->getNormalized());
                    $this->nsequations[$i]->setExpanded($expanded);
                } catch(\Exception $ex) {
                    // Do nothing, the default already is null
                }
            }
        }
        for ($i = 0; $i < $nrEquations; $i++) {
            if ($this->nsequations[$i]->getExpanded() !== null) {
                try {
                    $lineqstd = $LtreeTrf->collectByVars($this->nsequations[$i]->getExpanded());
                    $this->nsequations[$i]->setLineqstd($lineqstd);
                } catch(\Exception $ex) {
                    // Do nothing, the default already is null
                }
            }
        }
        // At this stage $this->nsequation has been completed
        $this->processNsequations();
    }

    /**
     * Deletes old entries in Tnsequations, and stores the new values.
     * 
     * @return void 
     */
    private function storeNsequations():void {
        // Remove all previously present equations
        $sql = 'DELETE FROM Tnsequations WHERE questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['questionid' => $this->id]);
        foreach ($this->nsequations as $nsequation) {
            $nsequation->store();
        }
    }

    private function processNsequations():void {
        $equations = [];
        foreach ($this->nsequations as $nsequation) {
            if ($nsequation->getLineqstd() !== null) {
                $equations[] = $nsequation->getLineqstd();
            }
        }
        $Lgauss = new \isLib\Lgauss();
        if (!empty($equations)) {
            try {
                $matrix = $Lgauss->makeMatrix($equations);
                $this->matrix = $matrix;
            } catch (\Exception $ex) {
                $this->matrix = null;
            }
        }
        if ($this->matrix !== null) {
            try {
                $this->varvalues = $Lgauss->solveLinEq($equations);
            } catch (\Exception $ex) {
                $this->varvalues = null;
            }
        } else {
            $this->varvalues = null;
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

    public function getNsequations():array {
        return $this->nsequations;
    }

    public function setNsequations(array $nseuations) {
        $this->nsequations = $nseuations;
    }

    public function getMatrix():array {
        return $this->matrix;
    }

    public function setMatrix(array|null $matrix) {
        $this->matrix = $matrix;
    }
}