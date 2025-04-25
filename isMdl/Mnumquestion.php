<?php

namespace isMdl;

use isLib\Lgauss;
use isLib\LtreeTrf;

class Mnumquestion extends MmodelBase
{

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

    /**
     * Numeric array of descriptors. Each descriptor has a veriable array in position 0 and a type in position 1
     * 
     * Type 0 results from regular backsubstitutions when the rank equals the number of variables. 
     * Type 1 results from backsubstitutions in triangular step matrices, where there are free variables.
     * 
     * The variable arrays in position 0 of the descriptor are type dependent.
     * 
     * For type 0 the array in position 0 is an associative array with variable names as keys and their float values as value
     * 
     * For type 1 the array in position 0 is an associative array with variable names as keys and a numeric array of summands as value 
     * Each summand is an array with a float value in position 0 and a variable name in position 1
     * The float value is the coefficient of the variable. Constants are treated like variables, but have name '1'
     * Ex.: z = 15 + 3y - 2x + 5 yelds a $result['z] = [[15, '1'], [3, 'y'], [-2, 'x'], [5. '1']]
     * 
     * @var array|null
     */
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

    function __construct(string $tablename)
    {
        parent::__construct($tablename);
    }

    public function load(int $id): bool
    {
        if ($this->exists($id)) {
            $sql = 'SELECT user, name, question, solution, matrix, varvalues FROM Tnumquestions WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $this->id = $id;
            $this->user = $row['user'];
            $this->name = $row['name'];
            $this->question = $row['question'];
            $this->solution = $row['solution'];
            $this->matrix = unserialize($row['matrix']);
            $this->varvalues = unserialize($row['varvalues']);
            $this->loadNsequations();
            return true;
        } else {
            return false;
        }
    }

    private function loadNsequations(): void
    {
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
    public function store(bool $new = false): int
    {
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
            $stmt->execute([
                'user' => $this->user,
                'name' => $this->name,
                'question' => $this->question,
                'solution' => $this->solution,
                'matrix' => $matrix,
                'varvalues' => $varvalues,
                'id' => $this->id
            ]);
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
    private function getFromOffset(array $elements, int $offset): mixed
    {
        foreach ($elements as $element) {
            if ($element[1] == $offset) {
                return $element;
            }
        }
        return false;
    }

    /**
     * Rebuilds $this->nsequations and $this->matrix from $this->solution
     * 
     * @return void 
     */
    public function processSolution(): void
    {
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
                $equation = $parts[0] . '-(' . $parts[1] . ')';
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
                } catch (\Exception $ex) {
                    // Do nothing, the default already is null
                }
            }
        }
        for ($i = 0; $i < $nrEquations; $i++) {
            if ($this->nsequations[$i]->getNormalized() !== null) {
                try {
                    $expanded = $LtreeTrf->partEvaluate($this->nsequations[$i]->getNormalized());
                    $this->nsequations[$i]->setExpanded($expanded);
                } catch (\Exception $ex) {
                    // Do nothing, the default already is null
                }
            }
        }
        for ($i = 0; $i < $nrEquations; $i++) {
            if ($this->nsequations[$i]->getExpanded() !== null) {
                try {
                    $lineqstd = $LtreeTrf->collectByVars($this->nsequations[$i]->getExpanded());
                    $this->nsequations[$i]->setLineqstd($lineqstd);
                } catch (\Exception $ex) {
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
    private function storeNsequations(): void
    {
        // Remove all previously present equations
        $sql = 'DELETE FROM Tnsequations WHERE questionid=:questionid';
        $stmt = \isLib\Ldb::prepare($sql);
        $stmt->execute(['questionid' => $this->id]);
        foreach ($this->nsequations as $nsequation) {
            $nsequation->store();
        }
    }

    private function processNsequations(): void
    {
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
        if ($this->varvalues[1] == 0) {
            // For the tyme beeing iteration works only with regular matrices
            $this->iterateSolutions();
        }
    }

    private function iterateSolutions(): void {
        // Get original parse trees of all equations
        $equations = [];
        foreach ($this->nsequations as $nsequation) {
            if ($nsequation->getParseTree() !== null) {
                $equations[] = $nsequation->getParseTree();
            }
        }
        $varvalues =$this->varvalues[0];
        $iterations = 0;
        $done = false;
        while (!$done) {
            try {
                $LtreeTrf = new \isLib\LtreeTrf(\isLib\Lconfig::CF_TRIG_UNIT);
                // Replace variables by known values where possible
                if ($varvalues !== null) {
                    $substitutedEquations = [];
                    foreach ($equations as $originalEquation) {
                        $substitutedEquations[] = $LtreeTrf->replaceVariables($originalEquation, $varvalues);
                    }
                }
                // Put original equations in linear equation standard form, but only as far as possible
                $lineqStdEquations = [];
                foreach ($substitutedEquations as $substitutedEquation) {
                    try {
                        $lineqStdEquations[] = $LtreeTrf->linEqStd($substitutedEquation);
                    } catch (\Exception $ex) {
                        // Do nothing
                    }
                }
                $Lgauss = new \isLib\Lgauss();
                $solutions = $Lgauss->solveLinEq($lineqStdEquations);
                if (empty($solutions[0])) {
                    $done = true;
                } else {
                   $varvalues = array_merge($varvalues, $solutions[0]);
                }
            } catch (\Exception $ex) {
                // Do nothing
                $a = 1;
            }
            $iterations ++;
            if ($iterations > 2) {
                $done = true;
            }
        }
        // Add the new solutions to the original ones
        foreach ($varvalues as $key => $varvalue) {
            if (!key_exists($key, $this->varvalues[0])) {
                $this->varvalues[0][$key] = $varvalue;
            }
        }
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    public function getSolution(): string
    {
        return $this->solution;
    }

    public function setSolution(string $solution): void
    {
        $this->solution = $solution;
    }

    public function getNsequations(): array
    {
        return $this->nsequations;
    }

    public function setNsequations(array $nseuations)
    {
        $this->nsequations = $nseuations;
    }

    public function getMatrix(): array
    {
        return $this->matrix;
    }

    public function setMatrix(array|null $matrix)
    {
        $this->matrix = $matrix;
    }

    public function getVarvalues(): array|null
    {
        return $this->varvalues;
    }
}
