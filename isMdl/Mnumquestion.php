<?php

namespace isMdl;

use isLib\Lgauss;
use isLib\LtreeTrf;
use PDOException;

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

    /****************************************
     * All mathML expressions in the solution HTML are numbered frmom 0 in the order in which they appear in HTML.
     * They are 1. decoded to ASCII, 2. equations are transformed in an ASCII expression, that must be 0, 
     * 3. the zero expressions are parsed.
     * If one of theses steps does not succeed, the input rules have not beeen folowed and the number of the
     * mathML Expression is noted in one of the following arrays.
     * Only if all arrays are empty the solution can be processed. This does not imply that it is correct.
     ****************************************/

    /**
     * 1. Cannot be decoded from mathML to ascii. Even if it is decodable it is not guaranteed to be valid ASCII
     * 
     * @var array
     */
    private array $notDecodable = [];

    /**
     * 2. Is not an equation
     * 
     * @var array
     */
    private array $notEquation = [];

    /**
     * 3. Cannot be parsed
     * 
     * @var array
     */
    private array $notParsable = [];

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
            if ($row['matrix'] === null) {
                $this->matrix = null;
            } else {
                $this->matrix = unserialize($row['matrix']);
            }
            if ($row['varvalues'] === null) {
                $this->varvalues = null;
            } else {
                $this->varvalues = unserialize($row['varvalues']);
            }
            $this->loadNsequations();
            $this->processSolution();
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
     * Creates an object of class Mnumquestion and stores its representation in the DB, returning the id in 'Tnumquestion'
     * 
     * @param int $user 
     * @param string $name 
     * @param string $question 
     * @param string $solution 
     * @return int 
     * @throws PDOException 
     */
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

    public function update(int $id, string $name, string $question, string $solution):bool {
        $loaded = $this->load($id);
        if ($loaded) {
            $this->name = $name;
            $this->question = $question;
            $this->solution = $solution;
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
            // Update
            $sql = 'UPDATE Tnumquestions SET name=:name, question=:question, solution=:solution, matrix=:matrix, varvalues=:varvalues WHERE id=:id';
            $stmt = \isLib\Ldb::prepare($sql);
            $stmt->execute([
                'name' => $this->name,
                'question' => $this->question,
                'solution' => $this->solution,
                'matrix' => $matrix,
                'varvalues' => $varvalues,
                'id' => $id
            ]);
            $this->storeNsequations();
            if ($this->processSolution()) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception('Numeric question '.$id,' was not found');
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
     * @return bool 
     */
    private function processSolution():bool
    {
        $mathmlExpressions = \isLib\Ltools::getMathmlExpressions($this->solution);
        // Detect expressions, which cannot be decoded
        $asciiExpressions = [];
        $LpresentationParser = new \isLib\LpresentationParser();
        foreach ($mathmlExpressions as $key => $mathmlExpression) {
            try {
                $asciiExpression = $LpresentationParser->getAsciiOutput($mathmlExpression[0]);
                $offset = $mathmlExpression[1];
                $asciiExpressions[$key] = [$asciiExpression, $offset];
            } catch (\Exception $ex) {
                $this->notDecodable[] = $key;
            }
        }
        // At this stage all decodable math is in $asciiExpressions. Erroneous mathML in $this->undecodableMathml
        $equations = [];
        foreach ($asciiExpressions as $key => $asciiExpression) {
            $expression = $asciiExpression[0];
            $offset = $asciiExpression[1];
            $parts = explode('=', $expression);
            if (count($parts) == 2) {
                $equation = $parts[0] . '-(' . $parts[1] . ')';
                $equations[$key] = [$equation, $offset];
            } else {
                $this->notEquation[] = $key;
            }
        }
        // At this stage all equations are in $equations as an ascii expression, that must be equal to zero.
        $this->nsequations = [];
        $nrEquations = count($equations);
        foreach ($equations as $key => $equation) {
            $nsequation = new \isMdl\Mnsequation('Tnsequations');
            $nsequation->setUser($this->user);
            $nsequation->setQuestionid($this->id);
            $offset = $equation[1];
            $mathml = $this->getFromOffset($mathmlExpressions, $offset);
            if ($mathml === false) {
                // Could not find searched offset
                \isLib\LmathError::setError(\isLib\LmathError::ORI_MNUMQUESTION, 1);
            }
            $nsequation->setMathml($mathml[0]);
            $nsequation->setSourceoffset($offset);
            try {
                $LasciiParser = new \isLib\LasciiParser($equation[0]);
                $LasciiParser->init();
                $parseTree = $LasciiParser->parse();
                $nsequation->setParsetree($parseTree);
            } catch (\Exception $ex) {
                $nsequation->setParsetree(null);
                $this->notParsable[] = $key;
            }
            $this->nsequations[] = $nsequation;
        }
        if (empty($this->notDecodable) && empty($this->notEquation) && empty($this->notParsable)) {
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
            return true;
        } else {
            return false;
        }
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

    /**
     * Returns the HTML given as solution with highlit illegal MathML formulas or false if everything is ok
     * 
     * @return string 
     */
    public function solutionErrHtml():string|false {
        if (empty($this->notDecodable) && empty($this->notEquation) && empty($this->notParsable)) {
            return false;
        }
        $html = $this->solution;
        // Handle $this->notDecodable
        $prefix = '<span class="blueformula">';
        $postfix = '</span>';
        foreach ($this->notDecodable as $nr) {
            $html = \isLib\Ltools::wrapMathmlExpression($html, $nr, $prefix, $postfix);
        }
        // Handle $this->notEquation. 
        $prefix = '<span class="yellowformula">';
        $postfix = '</span>';
        foreach ($this->notEquation as $nr) {
            $html = \isLib\Ltools::wrapMathmlExpression($html, $nr, $prefix, $postfix);
        }
        // Handle $this->notParsable. 
        $prefix = '<span class="redformula">';
        $postfix = '</span>';
        foreach ($this->notParsable as $nr) {
            $html = \isLib\Ltools::wrapMathmlExpression($html, $nr, $prefix, $postfix);
        }
        return $html;
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
