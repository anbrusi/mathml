<?php

namespace isMdl;

/**
 * This class is a common base for Mnumquestions and Mnumanswers, as far as solution handling is concerned
 * 
 * @package isMdl
 */
class Mnumsolution extends MdbTable {


    protected string $solution = '';

    /**
     * The original matrix built from lineqstd in $this->nsquestions
     * Position 0 is the matrix proper, position 1 a vector with the names of the variables ('1' for the constants)
     * 
     * @var array|null
     */
    protected array|null $matrix = null;

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
    protected array|null $varvalues = null;

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
    protected array $notDecodable = [];

    /**
     * 2. Is not an equation
     * 
     * @var array
     */
    protected array $notEquation = [];

    /**
     * 3. Cannot be parsed
     * 
     * @var array
     */
    protected array $notParsable = [];

    /**
     * Numeric array of Mnsequation. Can be empty
     * 
     * @var array
     */
    protected array $nsequations = [];

    function __construct(string $tablename)
    {
        parent::__construct($tablename);
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
     * $this->nsequations is built in memory, but not stored.
     * 
     * @return bool 
     */
    protected function processSolution(string $solution, int|null $user, int|null $questionid):bool
    {
        $this->solution = $solution;
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
            $nsequation->set('user', $user);
            $nsequation->set('questionid', $questionid);
            $offset = $equation[1];
            $mathml = $this->getFromOffset($mathmlExpressions, $offset);
            if ($mathml === false) {
                // Could not find searched offset
                \isLib\LmathError::setError(\isLib\LmathError::ORI_MNUMQUESTION, 1);
            }
            $nsequation->set('mathml', $mathml[0]);
            $nsequation->set('sourceoffset', $offset);
            try {
                $LasciiParser = new \isLib\LasciiParser($equation[0]);
                $LasciiParser->init();
                $parseTree = $LasciiParser->parse();
                $nsequation->set('parsetree', $parseTree);
            } catch (\Exception $ex) {
                $nsequation->set('parsetree', null);
                $this->notParsable[] = $key;
            }
            $this->nsequations[] = $nsequation;
        }
        if (empty($this->notDecodable) && empty($this->notEquation) && empty($this->notParsable)) {
            // At this stage $this->nsequations has been remade, but only properties up to 'parsetree' are set
            // Equations whose ascii expression could not be parsed have property 'parsetree' equal to null
            $LtreeTrf = new \isLib\LtreeTrf(\isLib\Lconfig::CF_TRIG_UNIT);
            for ($i = 0; $i < $nrEquations; $i++) {
                if ($this->nsequations[$i]->get('parsetree') !== null) {
                    try {
                        $normalized = $LtreeTrf->normalize($this->nsequations[$i]->get('parsetree'));
                        $this->nsequations[$i]->set('normalized', $normalized);
                    } catch (\Exception $ex) {
                        // Do nothing, the default already is null
                    }
                }
            }
            for ($i = 0; $i < $nrEquations; $i++) {
                if ($this->nsequations[$i]->get('normalized') !== null) {
                    try {
                        $expanded = $LtreeTrf->partEvaluate($this->nsequations[$i]->get('normalized'));
                        $this->nsequations[$i]->set('expanded', $expanded);
                    } catch (\Exception $ex) {
                        // Do nothing, the default already is null
                    }
                }
            }
            for ($i = 0; $i < $nrEquations; $i++) {
                if ($this->nsequations[$i]->get('expanded') !== null) {
                    try {
                        $lineqstd = $LtreeTrf->collectByVars($this->nsequations[$i]->get('expanded'));
                        $this->nsequations[$i]->set('lineqstd', $lineqstd);
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

    private function processNsequations(): void
    {
        $equations = [];
        foreach ($this->nsequations as $nsequation) {
            if ($nsequation->get('lineqstd') !== null) {
                $equations[] = $nsequation->get('lineqstd');
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
            if ($nsequation->get('parsetree') !== null) {
                $equations[] = $nsequation->get('parsetree');
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
     * Returns HTML for a legend of the colors used in $this->solutionErrHtml
     * 
     * @return string 
     */
    private function errorLegend():string {
        $html = '';
        $html .= '<br>';
        $html .= '<p><strong> Error legend:</strong></p>';
        $html .= '<span class="blueformula">';
        $html .= '&nbsp;';
        $html .= '</span>';
        $html .= '&nbsp;not decodable<br>';
        $html .= '<span class="yellowformula">';
        $html .= '&nbsp;';
        $html .= '</span>';
        $html .= '&nbsp;not an equation<br>';
        $html .= '<span class="redformula">';
        $html .= '&nbsp;';
        $html .= '</span>';
        $html .= '&nbsp;not parsable';
        return $html;
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
        // Add a legend
        $html .= $this->errorLegend();
        return $html;
    }

}