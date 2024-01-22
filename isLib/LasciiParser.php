<?php

namespace isLib;

/**
 *  
 * EBNF
 * ====
 * 
 * start            -> comparison
 * comparison	    -> expression [cmpop expression]
 * cmpop	        -> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	    -> ["-"] term {addop term}
 * addop		    -> "+" | "-" 
 * term			    -> factor {mulop factor}
 * mulop		    -> "*" | "/" | "?" | "**" | "&"  // "?" is an implicit "*", "**" is the cross product of two vectors
 * factor		    -> block {"^" factor}
 * block            -> atom | "(" expression ")"
 * atom             -> num | var | mathconst | functionname "(" expression ")" | functionnameTwo "(" expression "," expression ")"
 * functionname	    -> "abs" | "sqrt" | "exp" | "ln" | "log" | "sin" | "cos" | "tan" | "asin" | "acos" | "atan"
 * functionnameTwo  -> "max" | "min" | "rand"
 * 
 * -----------------------------------------------------
 * Old definitions
 * atom         -> boolval | array | matrix | vector 
 * base         -> mathconst | number | variable | funct
 * boolval      -> "true" | "false"
 * mathconst	-> "pi" | "e"
 * integer      -> digit {digit}
 * number		-> integer ["." integer] scale
 * scale		-> "E" ["-"] integer
 * digit		-> "0" | "1" | ... "9"
 * variable		-> alpha except reserved words
 * alpha		-> -small ascii letter-
 * funct		-> functionname "(" expression {"," expression} ")"
 * functionname	-> "abs" | "sqrt" | "exp" | "ln" | "log" | "sin" | "cos" | "tan" | "asin" | "acos" | "atan"	| "rnd"	| "max" | "min"
 * matrix		-> "matrix" "(" array {"," array} ")"
 * vector		-> "vector" "(" expression {"," expression} ")"
 * array		-> "{“ expression {"," expression} "}"
 * 
 * Exponentiation is right associative https://en.wikipedia.org/wiki/Exponentiation. This means a^b^c is a^(b^c) an NOT (a^b)^c.
 * factor implements this correctly.
 * 
 * @package isLib
 */
class LasciiParser
{

    private const BLANK_LINE = '                                                                                              ';
    private const NL = "\r\n";

    private const SP = '  ';

    private const EPSILON = 1E-9;

    /**
     * The ascii expression to parse
     * 
     * @var string
     */
    private string $asciiExpression;

    /**
     * Associative array with variable names as key and their value as value
     * Evaluate takes the values of variables from here
     * 
     * @var array|false
     */
    private array|false $variableList = false;

    /**
     * is set by parse to 'parse', by evaluate to 'evaluate'
     * Influences the representation of errors in $this->errtext
     * 
     * @var string
     */
    private string $activity = 'none';

    /**
     * The lexer used to retrieve the tokens of $this->asciiExpression
     * 
     * @var LasciiLexer
     */
    private \isLib\LasciiLexer $lexer;

    private string $errtext = '';

    /**
     * An associative array, which is recursively defined by
     * 
     * node -> '[' string 'tk', string 'type', [ node u | node l, node r | string 'value' ] ']'
     * // All nodes have string valued keys 'tk' and 'type',
     * // some may have one node valued key 'u', others two node valued keys 'l' and 'r'
     * // Types 'number', 'mathconst' and 'variable' have a string valued key 'value'. 
     *  
     * type -> 'cmpop' | 'matop' | 'number' | 'mathconst' | 'variable' | 'function'
     * tk -> operator | functionname
     * 
     * @var array|false
     */
    private array|false $parseTree = false;

    /**
     * The current token served by $this->lexer->getToken()
     * 
     * @var array|false
     */
    private array|false $token;

    /**
     * The number of the line pointed at by $this->charPointer
     * 
     * @var int
     */
    private int $txtLine;

    /**
     * The number of the column pointed at by $this->charPointer
     * 
     * @var int
     */
    private int $txtCol;

    /**
     * The symbol table built by the lexer
     * 
     * @var array
     */
    private array $symbolTable = [];

    /**
     * The unit used for trigonometry, possible values are 'deg' and 'rad'
     * 
     * @var string
     */
    private string $trigUnit = 'rad';

    function __construct(string $asciiExpression)
    {
        $this->asciiExpression = $asciiExpression;
    }

    public function init(): bool
    {
        $this->parseTree = false;
        $this->lexer = new \isLib\LasciiLexer($this->asciiExpression);
        $ok = $this->lexer->init();
        if ($ok) {
            // $this->symbolTable is a reference not a copy of the lexer symbol table !! Mind the & ampersand
            $this->symbolTable = &$this->lexer->getSymbolTable();
            $this->nextToken();
        }
        return $ok;
    }

    /**
     * Sets the values of variables used by the evaluator. 
     * It is not set automatically to a default value.
     * There is no check, that the variables are variables of $this->asciiExpression. So it can be set before parsing.
     * 
     * $variableList is an associative array with variable names as index nad variable values as values
     * 
     * @param array $variableList 
     * @return void 
     */
    public function setVariableList(array $variableList):void {
        $this->variableList = $variableList;
    }

    /**
     * Returns false if $this->asciiExpression has not been succesfully parsed,
     * returns a numeric array of detected variable names, after successful parsing.
     * THe array can be empty if there are no variables.
     * 
     * @return array|false 
     */
    public function getVariableNames():array|false {
        if ($this->parseTree === false) {
            $this->setError('Cannot get variable names. There is no parse tree');
            return false;
        }
        // Scan $this->symbolTable for variables
        $varnames = [];
        foreach ($this->symbolTable as $name => $value) {
            if ($value['type'] == 'variable') {
                $varnames[] = $name;
            }
        }
        return $varnames;
    }

    private function nextToken(): void
    {
        $this->token = $this->lexer->getToken();
        if ($this->token === false) {
            // Check lexer errors
            $lexerError = $this->lexer->getErrtext();
            if ($lexerError !== '') {
                $this->errtext = 'LEXER ERROR: '.$lexerError;
            }
        } else {
            if ($this->errtext == '') {
                // Do not increment after an error. So $this->txtLine and $this->txtCol point to the first error
                $this->txtLine = $this->token['ln'];
                $this->txtCol = $this->token['cl'];
            }
        }
        // We reached the end
    }

    private function setError(string $txt):void
    {
        // Retain only the first error
        if ($this->errtext == '') {
            if ($this->activity == 'parse') {
                $this->errtext = 'PARSER ERRR: '.$txt;
                if (isset($this->txtLine) && isset($this->txtCol)){
                    $position = ' ln '.$this->txtLine.' cl '.$this->txtCol;
                } else {
                    $position = ' No position found, possibly end of file';
                }
                $this->errtext .= $position;
            } elseif ($this->activity == 'evaluate') {
                $this->errtext = 'EVALUATION ERROR: '.$txt;
            } else {
                $this->errtext = 'UNKNOWN ACTIVITY: '.$txt;
            }
        }
    }

    /**
     * $this->parse does lexing and parsing in the same scan of $this->asciiExpression.
     * So variables in $this->sybolTable are available only after $this->parse has terminated.
     * 
     * start -> comparison
     * 
     * @return bool 
     */
    public function parse(): bool
    {
        $this->activity = 'parse';
        $comparison = $this->comparison();
        if ($comparison === false) {
            return false;
        }
        // Initially $rhis->parseTree is false.
        $this->parseTree = $comparison;
        $this->activity = 'none';
        return true;
    }

    public function evaluate():float|bool {
        $this->activity = 'evaluate';
        if ($this->parseTree === false) {
            // Does not overwrite a possible previous parse error
            $this->setError('No parse tree available. Parse first.');
            return false;
        }
        $evaluation = $this->evaluateNode($this->parseTree);
        $this->activity = 'none';
        return $evaluation;
    }

    /**
     * comparison -> expression [cmpop expression]
     * 
     * @return array|false
     */
    private function comparison(): array|false
    {
        $result = $this->expression();
        if ($result === false) {
            $this->setError('Expression expected');
            return false;
        }
        if ($this->token !== false) {
            if ($this->token['type'] == 'cmpop') {
                $token = $this->token;
                $this->nextToken();
                $expression = $this->expression();
                if ($expression === 'false') {
                    $this->setError('Expression expected');
                    return false;
                }
                $result = ['type' => 'cmpop', 'tk' => $token['tk'], 'l' => $result, 'r' => $expression];
            } else {
                $this->setError('Cmpop expected');
                return false;
            }
        }
        return $result;
    }

    /**
     * expression	-> ["-"] term {addop term}
     * 
     * @return array|false 
     */
    private function expression(): array|false
    {        
        $negative = false;
        if ($this->token['tk'] == '-') {
            // Build a unary minus node
            $negative = true;
            $this->nextToken();
        }
        $result = $this->term();
        if ($negative) {
            $result = ['type' => 'matop', 'tk' => '-', 'u' => $result];
        }
        while ( $this->token !== false && in_array($this->token['tk'], ['+', '-']) ) {
            $token = $this->token;
            $this->nextToken();
            $term = $this->term();
            if ($term === false) {
                $this->setError('Term expected');
                return false;
            }
            $result = ['type' => 'matop', 'tk' => $token['tk'], 'l' => $result, 'r' => $term];
        }        
        return $result;
    }
    /** 
     * term			-> factor {mulop factor}
     * 
     * @return array|false 
     */
    private function term(): array|false
    {
        $result = $this->factor();
        if ($result === false) {
            $this->setError('Factor expected');
            return false;
        }
        while ($this->token !== false && in_array($this->token['tk'], ['*', '/', '?'])) {
            $operator = $this->token['tk'];
            $this->nextToken();
            $factor = $this->factor();
            if ($factor === false) {
                $this->setError('Second factor expected in term after operator ' . $operator);
                return false;
            }
            $result = ['type' => 'matop', 'tk' => $operator, 'l' => $result, 'r' => $factor];
        }
        return $result;
    }

    /**
     * factor		-> block {"^" factor}
     * 
     * @return array|false 
     */
    private function factor():array|false
    {
        $result = $this->block();
        if ($result === false) {
            $this->setError('Block expected');
            return false;
        }
        while ($this->token !== false && $this->token['tk'] == '^') {
            $this -> nextToken();
            $factor = $this->factor();
            $result = ['type' => 'matop', 'tk' => '^', 'l' => $result, 'r' => $factor];
        }
        return $result;
    }

    /**
     * block     -> atom | "(" expression ")"
     * 
     * @return array|false 
     */
    private function block():array|false {
        if ($this->token === false) {
            $this->setError('Atom or (expression) expected');
            return false;
        }
        if ($this->token['tk'] == '(') {
            $this->nextToken();
            $result = $this->expression();
            if ($this->token !== false) {
                if ($this->token['tk'] == ')') {
                    $this->nextToken();
                } else {
                    $this->setError(') expected');
                    $result = false;
                }
            }
        } else {
            $result = $this->atom();
        }
        return $result;
    }

    /**
     * atom         -> num | var | mathconst |  functionname "(" expression ")"
     * 
     * @return array 
     */
    private function atom():array|false {
        if ($this->token === false) {
            $this->setError('Atom or (expression) expected');
            return false;
        }
        if ($this->token['type'] == 'number') {
            // 'value' is the number itself
            $result = ['tk' => $this->token['tk'], 'type' => 'number', 'value' => $this->token['tk']];
            $this->nextToken();
        } elseif ($this->token['type'] == 'id') {
            if (array_key_exists($this->token['tk'], $this->symbolTable)) {
                $symbolValue = $this->symbolTable[$this->token['tk']];
                if ($symbolValue['type'] == 'mathconst') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'mathconst', 'value' => $symbolValue['value']];
                    $this->nextToken();
                } elseif ($symbolValue['type'] == 'variable') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'variable', 'value' => $symbolValue['value']];
                    $this->nextToken();
                } elseif ($symbolValue['type'] == 'function') {
                    $args = $symbolValue['args'];
                    $functionname = $this->token['tk'];
                    $this->nextToken();
                    if ($this->token === false || $this->token['tk'] != '(') {
                        $this->setError('( expected');
                        return false;
                    }
                    $this->nextToken(); // Digest opening parenthesis
                    $expression = $this->expression();
                    if ($expression === false) {
                        $this->setError('Expression expected');
                        return false;
                    }
                    if ($args == 1) {
                        if ($this->token === false || $this->token['tk'] != ')') {
                            $this->setError(') expected');
                            return false;
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        $result = ['tk' => $functionname, 'type' => 'function', 'u' => $expression];
                    } elseif ($args == 2) {
                        if ($this->token === false || $this->token['tk'] !== ',') {
                            $this->setError(', expected');
                            return false;
                        }
                        $this->nextToken(); // Digestcomma
                        $expressionTwo = $this->expression();
                        if ($expressionTwo === false) {
                            $this->setError('Expression expected');
                            return false;
                        }
                        if ($this->token === false || $this->token['tk'] != ')') {
                            $this->setError(') expected');
                            return false;
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        $result = ['tk' => $functionname, 'type' => 'function', 'l' => $expression, 'r' => $expressionTwo];
                    } else {
                        $result = false;
                        $this->setError('unimplemented number of arguments '.$args);
                    }
                } else {
                    $result = false;
                    $this->setError('Unknown id '.$this->token['tk']);
                }
            } else {
                $result = false;
                $this->setError(('id '.$this->token['tk'].' not in symbol table'));
            }
        } else {
            $result = false;
            $this->setError('Atom expected, '.$this->token['type'].' found');
        }
        return $result;
    }

    public function getParseTree(): array
    {
        return $this->parseTree;
    }

   
    public function &getSymbolTable():array {
        return $this->symbolTable;
    }

    private function evaluateNode(array $node):float|bool {
        if ($this->errtext == '') {
            // type -> 'cmpop' | 'matop' | 'number' | 'mathconst' | 'variable' | 'function'
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
                default:
                    $this->setError('Unimplemented node type "'.$node['type'].'" in evaluation');
                    return 0;
            }
        } else {
            return 0;
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

    private function evaluateVariable(array $node):float {
        if ($this->variableList !== false && array_key_exists($node['tk'], $this->variableList)) {
            return $this->variableList[$node['tk']];
        } else {
            $this->setError('Variable '.$node['tk'].' is missing in variable list');
            return 0;
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
                $this->setError('Unimplemented function '.$funcName);
                return 0;
        }
    }

    /*******************************************************
     * The functions below are needed only for testing
     *******************************************************/

    public function showTokens(): string
    {
        $lexer = new \isLib\LasciiLexer($this->asciiExpression);
        return $lexer->showTokens();
    }

    public function showErrors(): string
    {
        if ($this->errtext != '') {
            $txt = '';
            if ($this->activity == 'parse') {
                if ($this->token !== false) {
                    $this->setError('Unexpected token '.$this->token['tk']);
                }
                $txtarray = explode("\r\n", $this->asciiExpression);
                foreach ($txtarray as $index => $subtext) {
                    $txt.= ($index + 1)."\t".$subtext."\r\n";
                    if ($this->txtLine == $index + 1) {
                        $txt.= ($index + 1)."\t".substr(self::BLANK_LINE, 0, $this->txtCol - 1).'^'."\r\n";
                    }
                }
                $txt .= self::NL;
            }
            $txt .= $this->errtext;
            return $txt;
        }
        return '';
    }

    private function space(int $level): string
    {
        $space = '';
        for ($i = 0; $i < $level; $i++) {
            $space .= self::SP;
        }
        return $space;
    }

    private function showSubtree(string &$txt, array|false $node, int $level): void
    {
        if ($node !== false) {
            if (isset($node['l'])) {
                $txt .= $this->showSubtree($txt, $node['l'], $level + 1);
            }
            $txt .= $this->space($level) . $node['tk'] . ' ' . $node['type'] . self::NL;
            if (isset($node['r'])) {
                $txt .= $this->showSubtree($txt, $node['r'], $level + 1);
            }
            if (isset($node['u'])) {
                $txt .= $this->showSubtree($txt, $node['u'], $level + 1);
            }
        }
    }

    public function showParseTree(): string
    {
        $txt = '';
        if ($this->parseTree !== false) {
            $this->showSubtree($txt, $this->parseTree, 0);
        } else {
            $txt.= 'No parse tree available';
        }
        return $txt;
    }

    public function showAsciiExpression():string {
        return $this->lexer->showExpression();
    }
}
