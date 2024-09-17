<?php

namespace isLib;

/**
 * Evaluates a parse tree passed to the constructor
 * 
 * INPUT: parse tree, variable values and trigonometric unit which are parameters of the constructor
 * 
 * OUTPUT: numeic or boolen value returned by $this->evaluate
 * 
 * NOTE all evaluations are wrapped by a if ($this->errtext == ') to stop any evaluation after the first error.
 * Since void cannot be set as an alternative return value, evaluations after an error return 0 or false depending on the expected normel return type
 * 
 * @package isLib
 */
class Levaluator {

    /**
     * Reals whose absolute value is below EPSILON are considered to be zero
     */
    private const EPSILON = 1E-9;
    /**
     * A valid parse tree such as those produced by LasciiParser
     * 
     * @var array
     */
    private array $parseTree;
    private array $variables;
    /**
    * The unit used for trigonometry, possible values are 'deg' and 'rad'. Default is 'deg'
    * 
    * @var string
    */
   private string $trigUnit = 'deg';

    /**
     * Requires a VALID parse tree and a list of varaibles with their values
     * 
     * @param array $parseTree 
     * @param array $variables Keys are the names, values the values of variables
     * @param string trigUnit Default is 'deg', alternative is 'rad'
     * @return void 
     */
    function __construct(array $parseTree, array $variables, string $trigUnit = 'deg') {
        $this->parseTree = $parseTree;
        $this->variables = $variables;
        $this->trigUnit = $trigUnit;
    }

    public function evaluate():float|bool {
        $evaluation = $this->evaluateNode($this->parseTree);
        return $evaluation;
    }

    private function evaluateNode(array $node):float|bool {
        // type -> 'cmpop' | 'matop' | 'number' | 'mathconst' | 'variable' | 'function' | 'boolop'
        switch ($node['type']) {
            case 'number':
                return floatval($node['value']);
            case 'mathconst':
                return $node['value'];
            case 'matop';
                return $this->evaluateMatop($node);
            case 'variable':
                return $this->evaluateVariable($node);
            case 'function':
                return $this->evaluateFunction($node);
            case 'cmpop':
                return $this->evaluateCmp($node);
            case 'boolop':
                return $this->evaluateBoolop($node);
            case 'boolvalue':
                return $this->evaluateBoolvalue($node);
            default:
                // Unimplemented node type in evaluation
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 1);
        }
    }


    private function isZero(float $proband):bool {
        return abs($proband) < self::EPSILON;
    }

    private function evaluateMatop(array $node):float {
        $operator = $node['tk'];
        if (isset($node['l']) && isset($node['r'])) {
            $left = $this->evaluateNode($node['l']);
            $right = $this->evaluateNode($node['r']);
            $unary = false;
        } elseif (isset($node['u'])) {
            $child = $this->evaluateNode($node['u']);
            $unary = true;
        }
        switch ($operator) {
            case '+':
                return $left + $right;
            case '-':
                if ($unary) {
                    return - $child;
                } else {
                    return $left - $right;
                }
            case '*':
            case '?':
                return $left * $right;
            case '/':
                if ($this->isZero($right)) {
                    // Division by zero
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 2);
                } else {
                    return $left / $right;
                }
            case '^':
                return pow($left, $right);
            default:
                // Unimplemante matop
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 3);
        }
    }

    private function evaluateVariable(array $node):float {
        if (array_key_exists($node['tk'], $this->variables)) {
            $value = $this->variables[$node['tk']];
            if (is_numeric($value)) {
                $value = floatval($value);
            }
            if (is_float($value) || is_bool($value)) {
                return $value;
            } else {
                // Variable cannot be evaluated to a float
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 4);
            }
        } else {
            // Variable is missing in variable list
            \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 5);
        }
    }

    private function degToRad(float $angle):float {
        return $angle / 180 * M_PI;
    }

    private function radToDeg(float $angle):float {
        return $angle / M_PI * 180;
    }

    private function evaluateFunction(array $node):float {
        $funcName = $node['tk'];
        switch ($funcName) {
            case 'abs':
                return abs($this->evaluateNode($node['u']));
            case 'sqrt':
                return sqrt($this->evaluateNode($node['u']));
            case 'exp':
                return exp($this->evaluateNode($node['u']));
            case 'ln';
                return log($this->evaluateNode($node['u']));
            case 'log':
                return log10($this->evaluateNode($node['u']));
            case 'sin':
                $argument = $this->evaluateNode($node['u']);
                if ($this->trigUnit == 'deg') {
                    $argument = $this->degToRad($argument);
                }
                return sin($argument);
            case 'cos':
                $argument = $this->evaluateNode($node['u']);
                if ($this->trigUnit == 'deg') {
                    $argument = $this->degToRad($argument);
                }
                return cos($argument);
            case 'tan':
                $argument = $this->evaluateNode($node['u']);
                if ($this->trigUnit == 'deg') {
                    $argument = $this->degToRad($argument);
                }
                return tan($argument);
            case 'asin':
                $value = asin($this->evaluateNode($node['u']));
                if ($this->trigUnit == 'deg') {
                    $value = $this->radToDeg($value);
                }    
                return $value;
            case 'acos':
                $value = acos($this->evaluateNode($node['u']));
                if ($this->trigUnit == 'deg') {
                    $value = $this->radToDeg($value);
                }      
                return $value;            
            case 'atan':
                $value = atan($this->evaluateNode($node['u']));
                if ($this->trigUnit == 'deg') {
                    $value = $this->radToDeg($value);
                }    
                return $value;   
            case 'max':
                $lValue = $this->evaluateNode($node['l']); 
                $rValue = $this->evaluateNode($node['r']); 
                if ($lValue >= $rValue) {
                    return $lValue;
                } else {
                    return $rValue;
                }        
            case 'min':
                $lValue = $this->evaluateNode($node['l']); 
                $rValue = $this->evaluateNode($node['r']); 
                if ($lValue <= $rValue) {
                    return $lValue;
                } else {
                    return $rValue;
                }      
            case 'rand':
                $lValue = intval($this->evaluateNode($node['l'])); 
                $rValue = intval($this->evaluateNode($node['r'])); 
                return mt_rand($lValue, $rValue);                              
            default:
                // Unimplemented function
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 6);
        }
    }

    /**
     * Returns true if the comparison is not notably false.
     * '>' means noticeably greater, so 1E-10 > 0 will be false
     * '>=' means not noticeably smaller so -1E-10 >= 0 will be true 
     * 
     * @param array $node 
     * @return bool 
     */
    private function evaluateCmp(array $node):bool {
        $leftValue = $this->evaluateNode($node['l']);
        if (!is_float($leftValue)) {
            // Left part of comparison is not numeric
            \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 7);
        }
        $rightValue = $this->evaluateNode($node['r']);
        if (!is_float($rightValue)) {
            // Right part of comparison is not numeric
            \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 8);
        }
        $symbol = $node['tk'];
        switch ($symbol) {
            case '=':
                return abs($leftValue - $rightValue) < self::EPSILON;
            case '<':
                return ($rightValue - $leftValue) > self::EPSILON;
            case '<=':
                return ($rightValue - $leftValue) > -self::EPSILON;
            case '>':
                return ($leftValue - $rightValue) > self::EPSILON;
            case '>=':
                return ($leftValue - $rightValue) > -self::EPSILON;
            case '<>':
                return abs($leftValue - $rightValue) >= self::EPSILON;
            default:
                // Unknown comparison symbol
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 11);
        }
    }

    private function evaluateBoolop(array $node):bool {
        if ($node['tk'] == '!') {
            // Unary operation
            $value = $this->evaluateNode($node['u']);
            return !$value;
        } else {
            // Binary operation
            $leftValue = $this->evaluateNode($node['l']);
            if (!is_bool($leftValue)) {
                // Left part of boolean operator is not bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 9);
            }
            $rightValue = $this->evaluateNode($node['r']);
            if (!is_bool($rightValue)) {
                // Right part of boolean operator is not bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 10);
            }
            $symbol = $node['tk'];
            switch ($symbol) {
                case '|':
                    return $leftValue || $rightValue;
                case '&':
                    return $leftValue && $rightValue;
                case '=':
                    return $leftValue == $rightValue;
                case '<>':
                    return $leftValue != $rightValue;
                default:
                    // Unknown boolop
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_EVALUATOR, 12);
            }
        }
    }

    private function evaluateBoolvalue(array $node):bool {
        if ($node['type'] == 'boolvalue') {
            if ($node['value'] == 'true') {
                return true;
            } elseif ($node['value'] == 'false') {
                return false;
            }
        }
    }
}