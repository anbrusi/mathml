<?php

namespace isLib;

/**
 * Evaluates a parse tree passed to the constructor
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
     * The text of an error message
     * 
     * @var string
     */
    private string $errtext = ''; 
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

    private function setError(string $txt):void
    {
        // Retain only the first error
        if ($this->errtext == '') {
            $this->errtext = 'EVALUATION ERROR: '.$txt;
        }
    }

    public function showErrors(): string
    {
        if ($this->errtext != '') {
            $txt = '';
            $txt .= $this->errtext;
            return $txt;
        }
        return '';
    }

    private function evaluateNode(array $node):float|bool {
        if ($this->errtext == '') {
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
                    $this->setError('Unimplemented node type "'.$node['type'].'" in evaluation');
                    return 0;
            }
        } else {
            return 0;
        }
    }


    private function isZero(float $proband):bool {
        if ($this->errtext == '') {
            return abs($proband) < self::EPSILON;
        }
        return false;
    }

    private function evaluateMatop(array $node):float {
        if ($this->errtext == '') {
            $operator = $node['tk'];
            if (isset($node['l']) && isset($node['r'])) {
                $left = $this->evaluateNode($node['l']);
                $right = $this->evaluateNode($node['r']);
                $unary = false;
            } elseif (isset($node['u'])) {
                $child = $this->evaluateNode($node['u']);
                $unary = true;
            }
            if ($this->errtext == '') {
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
                            $this->setError('Division by zero');
                            return 0;
                        } else {
                            return $left / $right;
                        }
                    case '^':
                        return pow($left, $right);
                    default:
                        $this->setError('Unimplemante matop '.$operator);
                        return 0;
                }
            }
        }
        return 0;
    }

    private function evaluateVariable(array $node):float {
        if ($this->errtext == '') {
            if (array_key_exists($node['tk'], $this->variables)) {
                $value = $this->variables[$node['tk']];
                if (is_numeric($value)) {
                    $value = floatval($value);
                }
                if (is_float($value) || is_bool($value)) {
                    return $value;
                } else {
                    $this->setError('Variable '.$node['tk'].' cannot be evaluated to a float');
                }
            } else {
                $this->setError('Variable '.$node['tk'].' is missing in variable list');
            }
        }
        return 0; // Satisfies the required return type. Processing should not continue, due to $this->errtext
    }

    private function degToRad(float $angle):float {
        return $angle / 180 * M_PI;
    }

    private function radToDeg(float $angle):float {
        return $angle / M_PI * 180;
    }

    private function evaluateFunction(array $node):float {
        if ($this->errtext == '') {
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
                    $this->setError('Unimplemented function '.$funcName);
                    return 0;
            }
        }
        return 0;
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
        if ($this->errtext == '') {
            $leftValue = $this->evaluateNode($node['l']);
            if (!is_float($leftValue)) {
                $this->setError('Left part of comparison is not numeric');
            }
            $rightValue = $this->evaluateNode($node['r']);
            if (!is_float($rightValue)) {
                $this->setError('Right part of comparison is not numeric');
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
                    return false;
            }
            return false;
        }
        return false;
    }

    private function evaluateBoolop(array $node):bool {
        if ($this->errtext == '') {
            if ($node['tk'] == '!') {
                // Unary operation
                $value = $this->evaluateNode($node['u']);
                return !$value;
            } else {
                // Binary operation
                $leftValue = $this->evaluateNode($node['l']);
                if (!is_bool($leftValue)) {
                    $this->setError('Left part of boolean operator is not bool');
                }
                $rightValue = $this->evaluateNode($node['r']);
                if (!is_bool($rightValue)) {
                    $this->setError('Right part of boolean operator is not bool');
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
                        return false;
                }
            }
            return false;
        }
        return false;
    }

    private function evaluateBoolvalue(array $node):bool {
        if ($this->errtext == '') {
            if ($node['type'] == 'boolvalue') {
                if ($node['value'] == 'true') {
                    return true;
                } elseif ($node['value'] == 'false') {
                    return false;
                }
            }
            return false;
        } 
        return false;
    }
}