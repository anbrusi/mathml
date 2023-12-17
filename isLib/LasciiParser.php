<?php

namespace isLib;

/**
 *  
 * EBNF
 * ====
 * 
 * block		-> expression [cmpop expression]
 * cmpop	    -> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	-> ["-"] term {addop term}
 * addop		-> "+" | "-" 
 * term			-> factor {mulop factor}
 * mulop		-> "*" | "/" | "?" | "**" | "&"  // "?" is an implicit "*", "**" is the cross product of two vectors
 * factor		-> atom | base ["^" factor] | "(" comparison ")"
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

    function __construct(string $asciiExpression)
    {
        $this->asciiExpression = $asciiExpression;
    }

    public function init(): bool
    {
        $this->lexer = new \isLib\LasciiLexer($this->asciiExpression);
        $ok = $this->lexer->init();
        $this->nextToken();
        return $ok;
    }

    private function nextToken(): void
    {
        $this->token = $this->lexer->getToken();
        if ($this->token === false) {
            // Check lexer errors
            $lexerError = $this->lexer->getErrtext();
            if ($lexerError !== '') {
                $this->token = false;
                $position = $this->lexer->getPosition();
                $this->errtext = 'LEXER ERROR: ' . $lexerError . ' at position ln ' . $position['ln'] . ' cl ' . $position['cl'];
            }
        }
        if ($this->errtext !== '') {
            $this->token = false;
            $position = $this->lexer->getPosition();
            $this->errtext = 'PARSER ERROR: ' . $this->errtext . ' at position ln ' . $position['ln'] . ' cl ' . $position['cl'];
        }
    }

    private function setError(string $txt): void
    {
        // Retain only the first error
        if ($this->errtext == '') {
            $this->errtext = $txt;
        }
    }

    public function parse(): bool
    {
        $block = $this->block();
        if ($block === false) {
            return false;
        }
        $this->parseTree = $block;
        return true;
    }

    /**
     * block -> expression [compareop expression]
     * 
     * @return array|false
     */
    private function block(): array|false
    {
        $block = $this->expression();
        if ($block === false) {
            $this->setError('Expression expected');
            return false;
        }
        if ($this->token !== false && $this->token['type'] == 'cmpop') {
            $token = $this->token;
            $this->nextToken();
            $expression2 = $this->expression();
            if ($expression2 === 'false') {
                $this->setError('Expression expected');
                return false;
            }
            $block = ['type' => 'cmpop', 'tk' => $token['tk'], 'l' => $block, 'r' => $expression2];
        }
        return $block;
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
        $expression = $this->term();
        if ($negative) {
            $expression = ['type' => 'matop', 'tk' => '-', 'u' => $expression];
        }
        return $expression;
    }
    /** 
     * term			-> factor {mulop factor}
     * 
     * @return array|false 
     */
    private function term(): array|false
    {
        $term = $this->factor();
        while ($this->token !== false && in_array($this->token['tk'], ['*', '/'])) {
            $operator = $this->token['tk'];
            $this->nextToken();
            $nextFactor = $this->factor();
            if ($nextFactor === false) {
                $this->setError('Second factor expected in term after operator ' . $operator);
                return false;
            }
            $term = ['type' => 'matop', 'tk' => $operator, 'l' => $term, 'r' => $nextFactor];
        }
        return $term;
    }

    private function factor(): array|false
    {
        $term = $this->token;
        $this->nextToken();
        return $term;
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
        // Do not use $thi->lexer, it would exhaust the input.
        $lexer = new \isLib\LasciiLexer($this->asciiExpression);
        $txt = '';
        $tokens = [];
        while ($token = $lexer->getToken()) {
            $tokens[] = $token;
        }
        foreach ($tokens as $index => $token) {
            // $txt .= $index."\t".$token['tk']."\t".' --'.$token['type']."\r\n";
            $txt .= $token['type'] . "\t" . $token['tk'] . "\r\n";
        }
        return $txt;
    }

    public function showErrors(): string
    {
        if ($this->errtext != '') {
            $txt = $this->lexer->showExpression();
            $txt .= self::NL;
            $pos = $this->lexer->getPosition();
            $txt .= $this->errtext . ' at position ln:' . $pos['ln'] . ', cl:' . $pos['cl'];
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

    private function showSubtree(string &$txt, array $node, int $level): void
    {
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
