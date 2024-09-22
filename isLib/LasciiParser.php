<?php

namespace isLib;

/**
 * Builds a parse tree from an ASCCI expression obeying to a custom syntax defined by the EBNF below
 * 
 * INPUT: ASCII expression in custom sintax passed to the constructor, $this->parse
 * 
 * OUTPUT: Parse tree returned by $this->parse, 
 *         $this->getVariableNames an array of the names of parsed variables (available only after successful parsing),
 *         $this->getTraversation an array of strings generated on enter 'E' and exit 'X' of prpductions in the used syntax
 *  
 * EBNF
 * ====
 * 
 * start            -> boolcomparison
 * boolcomparison   -> boolexpression { boolcmpop boolexpression }
 * boolcmpop        -> "=" | "<>"
 * boolexpreassion  -> boolterm { '|' boolterm }
 * boolterm         -> boolfactor { '&' boolfactor }
 * boolfactor       -> [ '!' ] boolatom
 * boolatom         ->  boolvalue | comparison
 * boolvalue        -> 'true' | 'false'
 * comparison	    -> expression [ cmpop expression ]
 * cmpop	        -> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	    -> ["-"] term {addop term}
 * addop		    -> "+" | "-" 
 * term			    -> factor { mulop factor }
 * mulop		    -> "*" | "/" | "?"  // "?" is an implicit "*"
 * factor		    -> block {"^" factor}
 * block            -> atom | "(" boolexpression ")"
 * atom             -> num | var | mathconst| boolvalue | functionname "(" boolexpression ")" | functionnameTwo "(" boolexpression "," boolexpression ")"
 * functionname	    -> "abs" | "sqrt" | "exp" | "ln" | "log" | "sin" | "cos" | "tan" | "asin" | "acos" | "atan"
 * functionnameTwo  -> "max" | "min" | "rand"
 * 
 * Exponentiation is right associative https://en.wikipedia.org/wiki/Exponentiation. This means a^b^c is a^(b^c) an NOT (a^b)^c.
 * The production factor implements this correctly.
 * 
 * UNARY OPERATORS
 * ===============
 * 
 * Unary minus in the production for expression acts on a term, not on a factor. Thus " -2 * 3 " is " -( 2 * 3 ) " and not " (-2) * 3 "
 * The numeric result is the same, but this choice prevents expressions such as " 2 * -3 "
 * 
 * Unary negation
 * 
 * The structure of nodes of the parse tree is recursively defined by:
 * 
 * node -> '[' string 'tk', string 'type', string 'restype', [ node u | node l, node r | string 'value' ] ']'
 * // All nodes have string valued keys 'tk' and 'type',
 * // some may have one node valued key 'u', others two node valued keys 'l' and 'r'
 * // Types 'number', 'mathconst' and 'variable' have a string valued key 'value'. 
 * // For the type 'variable' the name of the varible is registered in 'tk', 'value' is '-' unless it is specifically loaded e.g. for evaluation as in Levaluator
 *   
 * type -> 'cmpop' | 'matop' | 'boolop' | 'number' | 'mathconst' | 'variable' | 'function' 
 * cmpop -> '=' | '<>' | '<' | '<=' | '>' | '>='
 * matop -> 
 * boolop -> '|' | '&' | '!' (negation)
 * restype -> 'float' | 'bool'
 * tk -> operator | functionname
 * 
 * 
 * @package isLib
 */
class LasciiParser
{
    /**
     * The ascii expression to parse
     * 
     * @var string
     */
    private string $asciiExpression;

    /**
     * The lexer used to retrieve the tokens of $this->asciiExpression
     * 
     * @var LasciiLexer
     */
    private \isLib\LasciiLexer $lexer;

    /**
     * This variable is set by $this->parse. The purpose is to throw an exception in $this->getVariableNames if nothing has been parsed
     * 
     * An associative array, which is recursively defined by
     * 
     * node -> '[' string 'tk', string 'type', string 'restype', [ node u | node l, node r | string 'value' ] ']'
     * // All nodes have string valued keys 'tk' and 'type',
     * // some may have one node valued key 'u', others two node valued keys 'l' and 'r'
     * // Types 'number', 'mathconst' and 'variable' have a string valued key 'value'. 
     * // For the type 'variable' the name of the varible is registered in 'tk', 'value' is '-' unless it is specifically loaded e.g. for evaluation as in Levaluator
     *  
     * type -> 'cmpop' | 'matop' | 'boolop' | 'number' | 'mathconst' | 'variable' | 'function' 
     * cmpop -> '=' | '<>' | '<' | '<=' | '>' | '>='
     * matop -> 
     * boolop -> '|' | '&' | '!' (negation)
     * restype -> 'float' | 'bool'
     * tk -> operator | functionname
     * 
     * @var array
     */
    private array $parseTree = [];

    /**
     * The current token served by $this->lexer->getToken(). This is false if no token is available
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
     * An array of strings with the names of traversed functions on enter 'E' and exit 'X
     * 
     * @var array
     */
    private array $traversation = [];

    /**
     * @param string $asciiExpression 
     * @return void 
     */
    function __construct(string $asciiExpression)
    {
        $this->asciiExpression = $asciiExpression;
    }

    public function init():void {
        $this->lastToken = false;
        $this->tokenPending = false;
        $this->lexer = new \isLib\LasciiLexer($this->asciiExpression);
        $this->lexer->init();
        $this->symbolTable = &$this->lexer->getSymbolTable();
        $this->nextToken();
    }

    /**
     * Throws an exception if $this->asciiExpression has not been succesfully parsed,
     * Returns a numeric array of detected variable names, after successful parsing.
     * THe array can be empty if there are no variables.
     * 
     * @return array
     */
    public function getVariableNames():array {
        if ($this->parseTree === []) {
            // Cannot get variable names. There is no parse tree
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 25, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
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

    /**
     * Returns an array of strings generated on enter 'E' and exit 'X' of prpductions in the used syntax
     * 
     * @return array 
     */
    public function getTraversation():array {
        return $this->traversation;
    }

    /**
     * Gets the next token from the lexer and makes it the current token $thi->token
     * 
     * @return void 
     * @throws isMathException 
     */
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
        if (is_array($this->token)) {
            $this->txtLine = $this->token['ln'];
            $this->txtCol = $this->token['cl'];
        } elseif (is_array($this->lastToken)) {
            $this->txtLine = $this->lastToken['ln'];
            $this->txtCol = $this->lastToken['cl'] + 1;
        } else {
            // There is no token
            $this->txtLine = 1;
            $this->txtCol = 0;
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

    private function firstBool(array $token):bool {
        return $token['type'] == 'bool'; 
    }

    /**
     * $this->parse does lexing and parsing in the same scan of $this->asciiExpression.
     * So variables in $this->sybolTable are available only after $this->parse has terminated.
     * 
     * start -> boolcomparison
     * 
     * Returns an associative array, which is recursively defined by:
     * 
     * node -> '[' string 'tk', string 'type', string 'restype', [ node u | node l, node r | string 'value' ] ']'
     * // All nodes have string valued keys 'tk' and 'type',
     * // some may have one node valued key 'u', others two node valued keys 'l' and 'r'
     * // Types 'number', 'mathconst' and 'variable' have a string valued key 'value'. 
     * // For the type 'variable' the name of the varible is registered in 'tk', 'value' is '-' unless it is specifically loaded e.g. for evaluation as in Levaluator
     *  
     * type -> 'cmpop' | 'matop' | 'boolop' | 'number' | 'mathconst' | 'variable' | 'function' 
     * cmpop -> '=' | '<>' | '<' | '<=' | '>' | '>='
     * matop -> 
     * boolop -> '|' | '&' | '!' (negation)
     * restype -> 'float' | 'bool'
     * tk -> operator | functionname
     * 
     * @return array 
     */
    public function parse():array {
        $this->parseTree = $this->boolcomparison();
        return $this->parseTree;
    }

    /**
     * boolcomparison   -> boolexpression { boolcmpop boolexpression }
     * 
     * @return array
     */
    private function boolcomparison(): array {
        $this->traversation[] = 'E boolcomparison <-- TK: '.$this->token['tk'];
        $result = $this->boolexpression();
        while ($this->token['tk'] == '=' || $this->token['tk'] == '<>') {
            $boolcmpop = $this->token['tk'];
            $this->traversation[] = 'REP -> TK: '.$this->token['tk'];
            $this->nextToken();
            $boolexpression = $this->boolexpression();
            $result = ['tk' => $boolcmpop, 'type' => 'boolop', 'restype' => 'bool', 'l' => $result, 'r' => $boolexpression];
        }
        $this->traversation[] = 'X boolcomparison --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * boolexpreassion  -> boolterm {'|' boolterm}
     * 
     * @return array
     */
    private function boolexpression(): array {
        $this->traversation[] = 'E boolexpression <-- TK: '.$this->token['tk'];
        $result = $this->boolterm();
        while ($this->token !== false && $this->token['tk'] == '|') {
            $token = $this->token;
            $this->traversation[] = 'REP -> TK: '.$token['tk'];
            $this->nextToken();
            $boolterm = $this->boolterm();
            if ($result['restype'] != 'bool') {
                // Left term in “|“ must be bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 3, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            if ($boolterm['restype'] != 'bool') {
                // Right term in “|“ must be bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 4, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['tk' => $token['tk'], 'type' => 'boolop', 'restype' => 'bool', 'l' => $result, 'r' => $boolterm];
        }
        $this->traversation[] = 'X boolexpression --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * boolterm         -> boolfactor {'&' boolfactor}
     * 
     * @return array 
     */
    private function boolterm(): array {
        $this->traversation[] = 'E boolterm <-- TK: '.$this->token['tk'];
        $result = $this->boolfactor();
        while ($this->token !== false && $this->token['tk'] == '&') {
            $token = $this->token;
            $this->traversation[] = 'REP -> TK: '.$token['tk'];
            $this->nextToken();
            $boolfactor = $this->boolfactor();
            if ($result['restype'] != 'bool') {
                // Left term in “&“ must be bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 5, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            if ($boolfactor['restype'] != 'bool') {
                // Right term in “&“ must be bool
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 6, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['tk' => $token['tk'], 'type' => 'boolop', 'restype' => 'bool', 'l' => $result, 'r' => $boolfactor];
        }
        $this->traversation[] = 'X boolterm --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * boolfactor       -> [ '!' ] boolatom
     * 
     * @return array
     */
    private function boolfactor(): array {
        $this->traversation[] = 'E boolfactor <-- TK: '.$this->token['tk'];
        if ($this->token === false) {
            // 'boolatom or "!" expected'
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 2, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
        }
        $isNegated = false;
        if ($this->token['type'] == 'boolop' && $this->token['tk'] == '!') {
            $isNegated = true;
            $this->nextToken();
        }
        $result = $this->boolatom();
        if ($isNegated) {
            if ($result['restype'] != 'bool') {
                // Negation must be followed by a boolean
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 8, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['tk' => '!', 'type' => 'boolop', 'restype' => 'bool', 'u' => $result];
            $this->traversation[] = 'REP -> TK: !';
        }
        $this->traversation[] = 'X boolfactor --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * boolatom         -> boolvalue | comparison
     * 
     * @return array 
     */
    private function boolatom(): array {
        $this->traversation[] = 'E boolatom <-- TK: '.$this->token['tk'];
        if ($this->token === false) {
            // 'Unexpected end of input in boolatom'
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 1); 
        }
        if ($this->token['type'] == 'boolvalue') {
            $result = ['tk' => $this->token['tk'], 'type' => 'boolvalue', 'restype' => 'bool', 'value' => $this->token['tk']];
            $this->nextToken();
        // comparison
        } else {
            $result = $this->comparison();
        }
        $this->traversation[] = 'X boolatom --> TK: '.$result['tk'];
        return $result;
    }


    /**
     * comparison -> expression [cmpop expression]
     * 
     * @return array
     */
    private function comparison(): array
    {
        $result = $this->expression();
        $this->traversation[] = 'E comparison <-- TK: '.$this->token['tk'];
        // Transition from boolean algebra to ordinary algebra is determined by missing comparison token
        if ($this->token !== false) {
            if ($this->token['type'] == 'cmpop') {
                $token = $this->token;
                $this->nextToken();
                $expression = $this->expression();
                if ($result['restype'] != 'float') {
                    // Left part of comparison must be float
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 9, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                }
                if ($expression['restype'] != 'float') {
                    // Right part of comparison must be float
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 10, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                }
                $result = ['type' => 'cmpop', 'restype' => 'bool', 'tk' => $token['tk'], 'l' => $result, 'r' => $expression];
            }
        }
        $this->traversation[] = 'X comparison --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * expression	-> ["-"] term {addop term}
     * 
     * @return array
     */
    private function expression(): array
    {   
        $this->traversation[] = 'E expression <-- TK: '.$this->token['tk'];
        if ($this->token === false) {
            // Unexpected end of input in expression
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 18, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
        }     
        $negative = false;
        if ($this->token['tk'] == '-') {
            // Build a unary minus node
            $negative = true;
            $this->nextToken();
        }
        $result = $this->term();
        if ($negative) {
            if ($result['restype'] != 'float') {
                // Unary minus can be applied only to float value
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 11, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => '-', 'u' => $result];
        }
        while ( $this->token !== false && in_array($this->token['tk'], ['+', '-']) ) {
            $token = $this->token;
            $this->nextToken();
            $term = $this->term();
            if ($result['restype'] != 'float') {
                // Left part of addop must be of float type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 12, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            if ($term['restype'] != 'float') {
                // Right part of addop must be of float type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 13, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => $token['tk'], 'l' => $result, 'r' => $term];
        }    
        $this->traversation[] = 'X expression --> TK: '.$result['tk'];    
        return $result;
    }

    /** 
     * term			-> factor {mulop factor}
     * 
     * @return array
     */
    private function term(): array
    {
        $this->traversation[] = 'E term <-- TK: '.$this->token['tk'];
        $result = $this->factor();
        while ($this->token !== false && in_array($this->token['tk'], ['*', '/', '?'])) {
            $operator = $this->token['tk'];
            $this->nextToken();
            $factor = $this->factor();
            if ($result['restype'] != 'float') {
                // Left part of mulop must be of float type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 14, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            if ($factor['restype'] != 'float') {
                // Right part of mulop must be of float type
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 15, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => $operator, 'l' => $result, 'r' => $factor];
        }
        $this->traversation[] = 'X term --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * factor		-> block {"^" factor}
     * 
     * @return array
     */
    private function factor():array
    {
        $this->traversation[] = 'E factor <-- TK: '.$this->token['tk'];
        $result = $this->block();
        while ($this->token !== false && $this->token['tk'] == '^') {
            $this -> nextToken();
            $factor = $this->factor();
            if ($result['restype'] != 'float') {
                // Base in power must be float
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 16, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);

            }
            if ($factor['restype' != 'float']) {
                // Exponent in power must be float
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 17, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
            $result = ['type' => 'matop', 'restype' => 'float', 'tk' => '^', 'l' => $result, 'r' => $factor];
        }
        $this->traversation[] = 'X factor --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * block     -> atom | "(" boolexpression ")"
     * 
     * @return array
     */
    private function block():array { 
        $this->traversation[] = 'E block <-- TK: '.$this->token['tk'];
        if ($this->token === false) {
            // Atom or (boolexpression) expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 19, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
        }  
        if ($this->token['tk'] == '(') {
            $this->nextToken();
            $result = $this->boolexpression();
            if ($this->token !== false) {
                if ($this->token['tk'] == ')') {
                    $this->nextToken();
                } else {
                    // ) expected
                     \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 7, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                }
            }
        } else {
            $result = $this->atom();
        }
        $this->traversation[] = 'X block --> TK: '.$result['tk'];
        return $result;
    }

    /**
     * atom     -> num | var | mathconst| boolvalue | functionname "(" boolexpression ")" | functionnameTwo "(" boolexpression "," boolexpression ")"
     * 
     * @return array 
     */
    private function atom():array {
        $this->traversation[] = 'E atom <-- TK: '.$this->token['tk'];
        if ($this->token === false) {           
            // Atom or (boolexpression) expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 19, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
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
                        //  ( expected
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 20, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                    }
                    $this->nextToken(); // Digest opening parenthesis
                    $boolexpression = $this->boolexpression();
                    if ($args == 1) {
                        if ($this->token === false || $this->token['tk'] != ')') {
                            //  ) expected
                            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 7, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        // Do not check restype of boolexpression. We admit functions with boolean and with float arguments
                        $result = ['tk' => $functionname, 'type' => 'function', 'restype' => $symbolValue['restype'], 'u' => $boolexpression];
                    } elseif ($args == 2) {
                        if ($this->token === false || $this->token['tk'] !== ',') {
                            // , expected
                            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 21, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                        }
                        $this->nextToken(); // Digestcomma
                        $expressionTwo = $this->boolexpression();
                        if ($this->token === false || $this->token['tk'] != ')') {
                            //  ) expected
                            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 7, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                        }
                        $this->nextToken(); // Digest closing parenthesis
                        $result = ['tk' => $functionname, 'type' => 'function', 'restype' => $symbolValue['restype'], 'l' => $boolexpression, 'r' => $expressionTwo];
                    } else {
                        // unimplemented number of arguments
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 22, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                    }
                } else {
                    // mathconst, variable or function not in symbol table
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 22, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
                }
            } else {
                // mathconst, variable or function not in symbol table
                \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 23, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
            }
        } else {
            // Atom expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_PARSER, 24, ['ln' => $this->txtLine, 'cl' => $this->txtCol]);
        }
        $this->traversation[] = 'X atom --> TK: '.$result['tk'];
        return $result;
    }
   
}
