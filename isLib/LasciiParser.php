<?php

namespace isLib;

/**
 *  
 * EBNF
 * ====
 * 
 * start            -> boolexpression
 * boolexpreassion  -> boolterm {'|' boolterm}
 * boolterm         -> boolfactor {'&' boolfactor}
 * boolfactor       -> ['!'] basicboolfactor
 * basicboolfactor  -> boolvalue | comparison
 * boolvalue        -> 'true' | 'false'
 * comparison	    -> expression [cmpop expression]
 * cmpop	        -> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	    -> ["-"] term {addop term}
 * addop		    -> "+" | "-" 
 * term			    -> factor {mulop factor}
 * mulop		    -> "*" | "/" | "?"  // "?" is an implicit "*"
 * factor		    -> block {"^" factor}
 * block            -> atom | "(" boolexpression ")"
 * atom             -> num | var | mathconst| boolvalue | functionname "(" boolexpression ")" | functionnameTwo "(" boolexpression "," boolexpression ")"
 * functionname	    -> "abs" | "sqrt" | "exp" | "ln" | "log" | "sin" | "cos" | "tan" | "asin" | "acos" | "atan"
 * functionnameTwo  -> "max" | "min" | "rand"
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
     * is set by parse to 'parse', by evaluate to 'evaluate' and by init to 'presentation'
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
     * node -> '[' string 'tk', string 'type', string 'restype', [ node u | node l, node r | string 'value' ] ']'
     * // All nodes have string valued keys 'tk' and 'type',
     * // some may have one node valued key 'u', others two node valued keys 'l' and 'r'
     * // Types 'number', 'mathconst' and 'variable' have a string valued key 'value'. 
     *  
     * type -> 'cmpop' | 'matop' | 'number' | 'mathconst' | 'variable' | 'function' | 'paren'
     * restype -> 'float' | 'bool'
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
     * The last token retrieved from input
     * 
     * @var array|false
     */
    private array|false $lastToken;

    /**
     * If a fake token is returned, $tokenPending is set to true.
     * In this case the next real token is $this->lastToken and no new real token should be requested from the lexer
     * 
     * @var bool
     */
    private bool $tokenPending;

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
    private string $trigUnit = 'deg';

   
    /**
     * @param string $asciiExpression 
     * @return void 
     */
    function __construct(string $asciiExpression)
    {
        $this->asciiExpression = $asciiExpression;
    }

    public function init(): bool {
        $this->lastToken = false;
        $this->tokenPending = false;
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
        if ($this->tokenPending) {
            // We returned a fake token, so return the stored lastToken
            $this->token = $this->lastToken;
            $this->tokenPending = false;
        } else {
            $this->token = $this->lexer->getToken();
            // Check if a fake token must be returned in place of $this->token
            if ($this->implicitMultiplication($this->lastToken, $this->token)) {
                $this->tokenPending = true;
            }
            $this->lastToken = $this->token;
            if ($this->tokenPending) {
                // Return a fake token for implicit multiplication
                $this->token = ['tk' => '?', 'type' => 'impl', 'restype' => 'float',
                'ln' => $this->txtLine, 'cl' => $this->txtCol, 'chPtr' => 0];
            }
        }
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
            } elseif ($this->activity == 'presentation') {
                $this->errtext = 'PRESENTATION PARSER ERROR: '.$txt;
            } else {
                $this->errtext = 'UNKNOWN ACTIVITY: '.$txt;
            }
        }
    }

    /**
     * First token is a number.
     * Returns true if an implicit multiplication is required between number and $secondToken
     * 
     * @param array|false $secondToken 
     * @return bool 
     */
    private function numberFollowedBy(array|false $secondToken):bool {
        switch ($secondToken['type']) {
            case 'variable':
            case 'function':
            case 'mathconst':
                return true;
            case 'paren':
                return $secondToken['tk'] == '(';
            default:
                return false;
        }
    }

    /**
     * First token is a closing parentheses. 
     * Returns true if an implicit multiplication is required between closing parentheses and $secondToken
     * 
     * @param array|false $secondToken 
     * @return bool 
     */
    private function parenFollowedby(array|false $secondToken):bool {
        switch ($secondToken['type']) {
            case 'paren':
                return $secondToken['tk'] == '(';
            case 'number':
            case 'variable':
            case 'function':
            case 'mathconst':
                return true;
            default:
                return false;
        }
    }

    private function variableFollowedBy(array|false $secondToken):bool {
        switch ($secondToken['type']) {
            case 'paren':
                return $secondToken['tk'] == '(';
            case 'function':
                return true;
            default:
                return false;
        }
    }

    private function implicitMultiplication(array|false $firstToken, array|false $secondToken):bool {
        if ($firstToken === false || $secondToken === false) {
            return false;
        }
        switch ($firstToken['type']) {
            case 'number':
                return $this->numberFollowedBy($secondToken); // Number followed by $secondToken
            case 'paren':
                if ($firstToken['tk'] == ')') {
                    return $this->parenFollowedBy($secondToken); // Closing parentheses followed by $secondToken
                } else {
                    return false;
                }
            case 'variable':
                return $this->variableFollowedBy($secondToken);
            default:
                return false;
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
        $boolexpression = $this->boolexpression();
        if ($boolexpression === false) {
            return false;
        }
        // Initially $rhis->parseTree is false.
        $this->parseTree = $boolexpression;
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
     * boolexpreassion  -> boolterm {'|' boolterm}
     * 
     * @return array|false 
     */
    private function boolexpression(): array|false {
        $result = $this->boolterm();
        if ($result === false) {
            $this->setError('boolterm expected.');
            return false;
        }
        while ($this->token !== false && $this->token['tk'] == '|') {
            $token = $this->token;
            $this->nextToken();
            $boolterm = $this->boolterm();
            if ($boolterm === false) {
                $this->setError(('boolterm expected'));
                return false;
            }
            if ($result['restype'] != 'bool') {
                $this->setError('Left term in “|“ must be bool');
                return false;
            }
            if ($boolterm['restype'] != 'bool') {
                $this->setError('Right term in “|“ must be bool');
                return false;
            }
            $result = ['tk' => $token['tk'], 'type' => 'boolop', 'restype' => 'bool', 'l' => $result, 'r' => $boolterm];
        }
        return $result;
    }

    /**
     * boolterm         -> boolfactor {'&' boolfactor}
     * 
     * @return array|false 
     */
    private function boolterm(): array|false {
        $result = $this->boolfactor();
        if ($result === false) {
            $this->setError('boolfactor expected.');
        }
        while ($this->token !== false && $this->token['tk'] == '&') {
            $token = $this->token;
            $this->nextToken();
            $boolfactor = $this->boolfactor();
            if ($boolfactor === false) {
                $this->setError('boolfactor expected');
                return false;
            }
            if ($result['restype'] != 'bool') {
                $this->setError('Left term in “&“ must be bool');
                return false;
            }
            if ($boolfactor['restype'] != 'bool') {
                $this->setError('Right term in “&“ must be bool');
                return false;
            }
            $result = ['tk' => $token['tk'], 'type' => 'boolop', 'restype' => 'bool', 'l' => $result, 'r' => $boolfactor];
        }
        return $result;
    }

    /**
     * basicboolfactor  -> boolvalue | comparison | '(' boolexpression ')'
     * 
     * @return array|false 
     */
    private function basicboolfactor(): array|false {
        if ($this->token === false) {
            $this->setError('comparison or (boolexpression) expected');
            return false;
        }
        if ($this->token['type'] == 'boolvalue') {
            $result = ['tk' => $this->token['tk'], 'type' => 'boolvalue', 'restype' => 'bool', 'value' => $this->token['tk']];
            $this->nextToken();
        // comparison
        } else {
            $result = $this->comparison();
        }

        return $result;
    }

    /**
     * boolfactor       -> ['!'] basicboolfactor
     * 
     * @return array|false 
     */
    private function boolfactor(): array|false {
        if ($this->token === false) {
            $this->setError('basicboolfactor or "!" expected');
            return false;
        }
        $isNegated = false;
        if ($this->token['type'] == 'boolop' && $this->token['tk'] == '!') {
            $isNegated = true;
            $this->nextToken();
        }
        $result = $this->basicboolfactor();
        if ($isNegated) {
            if ($result['restype'] != 'bool') {
                $this->setError('Negation of non boolean');
                return false;
            }
            $result = ['tk' => '!', 'type' => 'boolop', 'restype' => 'bool', 'u' => $result];
        }
        return $result;
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
                if ($result['restype'] != 'float') {
                    $this->setError('Left part of comparison must be float');
                    return false;
                }
                if ($expression['restype'] != 'float') {
                    $this->setError('Right part of comparison must be float');
                    return false;
                }
                $result = ['type' => 'cmpop', 'restype' => 'bool', 'tk' => $token['tk'], 'l' => $result, 'r' => $expression];
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
            if ($result['restype'] != 'float') {
                $this->setError('Unary minus can be applied only to float value');
                return false;
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => '-', 'u' => $result];
        }
        while ( $this->token !== false && in_array($this->token['tk'], ['+', '-']) ) {
            $token = $this->token;
            $this->nextToken();
            $term = $this->term();
            if ($term === false) {
                $this->setError('Term expected');
                return false;
            }
            if ($result['restype'] != 'float') {
                $this->setError('Left part of "'.$token['tk'].'" must be float');
                return false;
            }
            if ($term['restype'] != 'float') {
                $this->setError('Right part of "'.$token['tk'].'" must be float');
                return false;
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => $token['tk'], 'l' => $result, 'r' => $term];
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
            if ($result['restype'] != 'float') {
                $this->setError('Left part of "'.$operator.'" must be float');
                return false;
            }
            if ($factor['restype'] != 'float') {
                $this->setError('Right part of "'.$operator.'" must be float');
                return false;
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => $operator, 'l' => $result, 'r' => $factor];
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
            if ($result['restype'] != 'float') {
                $this->setError('Base must be float');
                return false;
            }
            if ($factor['restype' != 'float']) {
                $this->setError('Exponent must be float');
                return false;
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => '^', 'l' => $result, 'r' => $factor];
        }
        return $result;
    }

    /**
     * block     -> atom | "(" boolexpression ")"
     * 
     * @return array|false 
     */
    private function block():array|false {
        if ($this->token === false) {
            $this->setError('Atom or (boolexpression) expected');
            return false;
        }
        if ($this->token['tk'] == '(') {
            $this->nextToken();
            $result = $this->boolexpression();
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
     * atom     -> num | var | mathconst| boolvalue | functionname "(" boolexpression ")" | functionnameTwo "(" boolexpression "," boolexpression ")"
     * 
     * @return array 
     */
    private function atom():array|false {
        if ($this->token === false) {
            $this->setError('Atom or (expression) expected');
            return false;
        }
        // num
        if ($this->token['type'] == 'number') {
            // 'value' is the number itself
            $result = ['tk' => $this->token['tk'], 'type' => 'number', 'restype' => 'float', 'value' => $this->token['tk']];
            $this->nextToken();
        } elseif (in_array($this->token['type'], ['mathconst', 'variable', 'function'])) {
            if (array_key_exists($this->token['tk'], $this->symbolTable)) {
                $symbolValue = $this->symbolTable[$this->token['tk']];
                if ($symbolValue['type'] == 'mathconst') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'mathconst', 'restype' => $symbolValue['restype'], 'value' => $symbolValue['value']];
                    $this->nextToken();
                } elseif ($symbolValue['type'] == 'variable') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'variable', 'restype' => $symbolValue['restype'], 'value' => $symbolValue['value']];
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
                    $boolexpression = $this->boolexpression();
                    if ($boolexpression === false) {
                        $this->setError('Expression expected');
                        return false;
                    }
                    if ($args == 1) {
                        if ($this->token === false || $this->token['tk'] != ')') {
                            $this->setError(') expected');
                            return false;
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        // Do not check restype of boolexpression. We admit functions with boolean and with float arguments
                        $result = ['tk' => $functionname, 'type' => 'function', 'restype' => $symbolValue['restype'], 'u' => $boolexpression];
                    } elseif ($args == 2) {
                        if ($this->token === false || $this->token['tk'] !== ',') {
                            $this->setError(', expected');
                            return false;
                        }
                        $this->nextToken(); // Digestcomma
                        $expressionTwo = $this->boolexpression();
                        if ($expressionTwo === false) {
                            $this->setError('Expression expected');
                            return false;
                        }
                        if ($this->token === false || $this->token['tk'] != ')') {
                            $this->setError(') expected');
                            return false;
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        $result = ['tk' => $functionname, 'type' => 'function', 'restype' => $symbolValue['restype'], 'l' => $boolexpression, 'r' => $expressionTwo];
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

    private function evaluateBoolop(array $node):bool {
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
                default:
                    return false;
            }
        }
        return false;
    }

    private function evaluateBoolvalue(array $node):bool {
        if ($node['type'] == 'boolvalue') {
            if ($node['value'] == 'true') {
                return true;
            } elseif ($node['value'] == 'false') {
                return false;
            }
        }
        return false;
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
