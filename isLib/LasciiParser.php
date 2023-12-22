<?php

namespace isLib;

/**
 *  
 * EBNF
 * ====
 * 
 * start        -> comparison
 * comparison	-> expression [cmpop expression]
 * cmpop	    -> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	-> ["-"] term {addop term}
 * addop		-> "+" | "-" 
 * term			-> factor {mulop factor}
 * mulop		-> "*" | "/" | "?" | "**" | "&"  // "?" is an implicit "*", "**" is the cross product of two vectors
 * factor		-> block {"^" factor}
 * block        -> atom | "(" expression ")"
 * atom         -> num | var | mathconst | functionname "(" expression ")"
 * functionname	-> "abs" | "sqrt" | "exp" | "ln" | "log" | "sin" | "cos" | "tan" | "asin" | "acos" | "atan"	| "rnd"	| "max" | "min"
 * 
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

    private const NL = "\r\n";

    private const SP = '  ';

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

    private string $errtext = '';

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

    function __construct(string $asciiExpression)
    {
        $this->asciiExpression = $asciiExpression;
    }

    public function init(): bool
    {
        $this->lexer = new \isLib\LasciiLexer($this->asciiExpression);
        $ok = $this->lexer->init();
        if ($ok) {
            // $this->symbolTable is a reference not a copy of the lexer symbol table !! Mind the & ampersand
            $this->symbolTable = &$this->lexer->getSymbolTable();
            $this->nextToken();
        }
        return $ok;
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
            $this->txtLine = $this->token['ln'];
            $this->txtCol = $this->token['cl'];
        }
        // We reached the end
    }

    private function setError(string $txt):void
    {
        // Retain only the first error
        if ($this->errtext == '') {
            $this->errtext = 'PARSER ERRR: '.$txt;
            if (isset($this->txtLine) && isset($this->txtCol)){
                $position = ' ln '.$this->txtLine.' cl '.$this->txtCol;
            } else {
                $position = ' No position found, possibly end of file';
            }
            $this->errtext .= $position;
        }
    }

    /**
     * 
     * start -> comparison
     * 
     * @return bool 
     */
    public function parse(): bool
    {
        $comparison = $this->comparison();
        if ($comparison === false) {
            return false;
        }
        $this->parseTree = $comparison;
        return true;
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
            $result = ['tk' => $this->token['tk'], 'type' => 'number'];
            $this->nextToken();
        } elseif ($this->token['type'] == 'id') {
            if (array_key_exists($this->token['tk'], $this->symbolTable)) {
                $symbolValue = $this->symbolTable[$this->token['tk']];
                if ($symbolValue['type'] == 'mathconst') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'mathconst'];
                    $this->nextToken();
                } elseif ($symbolValue['type'] == 'variable') {
                    $result = ['tk' => $this->token['tk'], 'type' => 'variable'];
                    $this->nextToken();
                } elseif ($symbolValue['type'] == 'function') {
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
                    if ($this->token === false || $this->token['tk'] != ')') {
                        $this->setError(') expected');
                        return false;
                    }
                    $this->nextToken(); // Digest closing parenthesis
                    $result = ['tk' => $functionname, 'type' => 'function', 'u' => $expression];
                } else {
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
            $txt = $this->lexer->showExpression();
            $txt .= self::NL;
            if ($this->token !== false) {
                $this->setError('Unexpected token '.$this->token['tk']);
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

    /*
    public function showSymbolTable():string {
        $txt = '';
        foreach($this->symbolTable as $index => $symbol) {
            $txt .= $index."\t".$symbol['type']."\r\n";
        }
        return $txt;
    }
    */
}
