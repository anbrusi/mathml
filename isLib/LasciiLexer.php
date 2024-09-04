<?php

namespace isLib;

/**
 * Splits an ascii expression into tokens.
 * 
 * Each token is an arry with the following keys:
 *      'type': 'unknown' | 'number' | 'variable' | 'mathconst' | 'function' | 'matop' | 'comma' | 'cmpop' | 'paren' | 'boolop' | 'boolvalue'
 *      'restype': 'float' | 'bool' | 'unknown'
 *      'tk': the input symbol like 'sin', '+', ')', '17.9' etc. 
 * Type dependent keys are
 *      'args' for type 'function'  The value of this key is the number of arguments of functions
 *      'value' for type 'mathconst' The value of this key is the numeric value of the mathematical constant
 * 
 * @package isLib
 */
class LasciiLexer {

    /**
     * The raw expression, as passed to the constructor and displayed in $this->showExpression
     * 
     * @var string
     */
    private string $asciiExpression = '';

    /**
     * The asciimath expression as an array of multibyte characters
     * 
     * @var array
     */
    private array $input;

    /**
     * Index in $input of the character, that will be retrieved next by $this->getChar
     * 
     * @var int
     */
    private int $charPointer;

    /**
     * Current character in $this->input belonging to the alphabet or false if there is none
     * 
     * @var string|false
     */
    private string|false $char;

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
     * An associative array indexed by identifier with array values
     * The values are associatve arrays with keys 'type', and other type dependent keys.
     * type ='function' has additional key 'args'
     * type = 'matconst' has additional key 'value'
     * type = 'variable' has an additional meaningless key 'value', whose value is '-'.
     * 
     * @var array
     */
    private array $symbolTable = [];

    /**
     * Text describing the last error
     * 
     * @var string
     */
    private string $errtext = '';

    function __construct(string $asciiExpression) {
        $this->asciiExpression = $asciiExpression;
        $this->input = mb_str_split($asciiExpression);
        $this->charPointer = 0;
        $this->txtLine = 1;
        $this->txtCol = 0;
        $this->setReservedIdentifiers();
        // Set the initially current character
        $this->getNextChar();
    }

    public function init():bool {
        return true;
    }

    private function setReservedIdentifiers():void {
        $functionNames = ['abs', 'sqrt', 'exp', 'ln', 'log', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'rand', 'max', 'min']; 
        foreach ($functionNames as $name) {
            $this->symbolTable[$name] = ['type' => 'function', 'restype' => 'float', 'args' => 1];
        }
        // Functions with more than 1 variable
        $this->symbolTable['max']['args'] = 2;
        $this->symbolTable['min']['args'] = 2;
        $this->symbolTable['rand']['args'] = 2;
        // Mathematical constants
        $this->symbolTable['e'] = ['type' => 'mathconst', 'restype' => 'float', 'value' => M_E];
        $this->symbolTable['pi'] = ['type' => 'mathconst', 'restype' => 'float', 'value' => M_PI];
        // Boolean values
        $this->symbolTable['true'] = ['type' => 'boolvalue', 'restype' => 'bool', 'value' => 'true'];
        $this->symbolTable['false'] = ['type' => 'boolvalue', 'restype' => 'bool', 'value' => 'false'];
    }

    /**
     * Registers $text as an error and stops at the next call to 
     * 
     * @param string $text 
     * @return void 
     */
    private function error(string $text):void {
        $this->errtext = $text;
        $this->char = false;
    }

    /**
     * When the lexer initializes $this->char holds the first input character, that belongs to the alphabeth or false
     * $this->getNextChar retrieves the following character in the alphabet and places it in $this->getChar
     * The lexer makes use of $this->character and calls $this->nextChar when done to digest it.
     * 
     * @return string|false 
     */
    private function getNextChar():void {
        $newline = false;
        while ($this->charPointer < count($this->input) && !$this->inAlphabet($this->input[$this->charPointer]) )  {
            if ($this->input[$this->charPointer] == "\n") {
                $newline = true;
            }
            $this->charPointer += 1;
            $this->txtCol += 1;
            if ($newline) {
                $this->txtLine +=1;
                $this->txtCol = 0;
            }
        }
        if ($this->charPointer < count($this->input)) {
            $this->char = $this->input[$this->charPointer];
            $this->charPointer += 1;
            $this->txtCol += 1;
        } else {
            $this->char = false;
        }
    }

    /**
     * Returns the next token in $this->input or false if an error occurred
     * A text describing the error is registered in $this->errtext
     * The position is recorded in $this->errpos
     * 
     * @return array|false 
     */
    public function getToken():array|false {
        if ($this->char === false) {
            return false;
        }
        if ($this->isDigit($this->char)) {
            $token = $this->readNum();
        } elseif ($this->firstInMatop($this->char)) {
            $token = $this->readMatop();
        } elseif ($this->firstInBoolop($this->char)) {
            $token = $this->readBoolop();
        } elseif ($this->firstInCmpop($this->char)) {
            $token = $this->readCmpop();
        } elseif ($this->firstInParenthesis($this->char)) {
            $token = $this->readParenthesis();
        } elseif ($this->firstInIdentifiers($this->char)) {
            $token = $this->readIdentifier();
        } elseif ($this->char == ',') {
            $token = $this->readComma();
        } else {
            $token = ['tk' => 'unimplemented: '.$this->char, 'type' => 'unknown', 'restype' => 'unknown',
                      'ln' => $this->txtLine, 'cl' => $this->txtCol, 'chPtr' => $this->charPointer];
            $this->getNextChar();
        }
        return $token;
    }

    public function getPosition():array {
        return ['ln' => $this->txtLine, 'cl' => $this->txtCol];
    }

    public function getErrtext():string {
        return $this->errtext;
    }

    /**
     * NOTE the & ampersand
     * 
     * Returns a pointer to $this->symbolTable. It is necessary to pass the symbol table by reference 
     * to the parser, because variables are registered in the course of lexing, which is simultaneous to parsing.
     * 
     * @return array 
     */
    public function &getSymbolTable():array {
        return $this->symbolTable;
    }

    private function inAlphabet(string $char):bool {
        if ( $this->isDigit($char) ||
             $this->isAlpha($char) ||
             $this->isSpecialChar($char)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Returns true iff $char is an alphabetic character belonging to ASCII
     * No distinction between upper and lower case is made
     * 
     * @param string $char 
     * @return bool 
     */
    private function isAlpha(string $char):bool {
        $ch = strtolower($char);
		return ((ord($ch) >= 97) && (ord($ch) <= 122));
    }

    /**
     * Returns true iff $char is a digit between '0' and '9'
     * 
     * @param string $char 
     * @return bool 
     */
    private function isDigit(string $char):bool {
        return ((ord($char) >= 48) && (ord($char) <= 57));
    }

    /**
     * Returns true iff $char belongs to the mathml alfabeth, but is neither a digit nor an alpha
     * 
     * @param string $char 
     * @return bool 
     */
    private function isSpecialChar(string $char):bool {
        return in_array($char, ['+', '-', '*', '/', '=', '<', '>', '.', '(', ')', '^', ',', '&', '|', '!']);
    } 

    /**
     * Returns true iff $char is the first character of a mathematical operation (provision is made for multicharacter operations)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInMatop(string $char):bool {
        return in_array($char, ['+', '-', '*', '/', '^']);
    }

    /**
     * Returns true iff $char is a boolean operator
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInBoolop(string $char):bool {
        return in_array($char, ['&', '|', '!']);
    }

    /**
     * Returns true iff $char is the first character of a compare operation (provision is made for multicharacter operations)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInCmpop(string $char):bool {
        return in_array($char, ['=', '<', '>']);
    }

    /**
     * Returns true iff $char is the first character of a parentesis (provision is made for multicharacter parenthesis)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInParenthesis(string $char):bool {
        return in_array($char, ['(', ')']);
    }

    /**
     * Returns true iff $char is an alpha character. Identifiers consist of alpha(s) possibly followed by digit(s)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInIdentifiers(string $char):bool {
        return $this->isAlpha($char);
    }

    /**
     * Returns a positive integer or an empty string
     * 
     * @return string 
     */
    private function readInt():string {
        $txt = '';
        while ($this->isDigit($this->char)) {
            $txt .= $this->char;
            $this->getNextChar();
        }
        return $txt;
    }

    /**
     * If readNum is entered, it is guaranteed that $firstChar is a digit
     * 
     * @param string $firstChar 
     * @return array 
     */
    private function readNum():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $hasDecpart = false;
        $hasEpart = false;
        $txt = $this->readInt();
        if ($this->char == '.') {
            $txt .= $this->char;
            $hasDecpart = true;
            $this->getNextChar();
        }
        if ($hasDecpart) {
            $decpart = $this->readInt();
            if ($decpart === '') {
                $this->error('Decimal part of number is missing');
            } else {
                $txt .= $decpart;
            }
        }
        if ($this->char == 'E') {
            $txt .= $this->char;
            $hasEpart = true;
            $this->getNextChar();
        }
        if ($hasEpart) {
            if ($this->char == '-') {
                $txt .= $this->char;
                $this->getNextChar();
            }
            $scale = $this->readInt();
            if ($scale == '') {
                $this->error('Scale missing after E in number');
            } else {
                $txt .= $scale;
            }
        }
        return ['tk' => $txt, 'type' => 'number', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token denoting a mathematical operator
     * 
     * @return array 
     */
    private function readMatop():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'matop', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token denoting a boolean operator
     * 
     * @return array 
     */
    private function readBoolop():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'boolop', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    private function readComma():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'comma', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token denoting a parentesis
     * 
     * @return array 
     */
    private function readCmpop():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = $this->char;
        $this->getNextChar();
        if (in_array($txt, ['>', '<']) && $this->char == '=') {
            $txt .= $this->char;
            $this->getNextChar();
        }
        // We code 'different' as <>
        if ($txt == '<' && $this->char == '>') {
            $txt .= $this->char;
            $this->getNextChar();
        }
        return ['tk' => $txt, 'type' => 'cmpop', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token denoting a parentesis
     * 
     * @return array 
     */
    private function readParenthesis():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'paren', 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token for an identifier
     * 
     * @return array 
     */
    private function readIdentifier():array {
        $line = $this->txtLine;
        $col = $this->txtCol;
        $chPtr = $this->charPointer;
        $txt = '';
        while ($this->isAlpha($this->char)) {
            $txt .= $this->char;
            $this->getNextChar();
        }
        if ($this->isDigit($this->char)) {
            $txt .= $this->readInt();
        }
        // Enter the id in the symbol table as a variable if it is a new identifier
        if (!array_key_exists($txt, $this->symbolTable)) {
            $this->symbolTable[$txt] = ['type' => 'variable', 'restype' => 'float', 'value' => '-'];
        } 
        $tokenType = $this->symbolTable[$txt]['type'];
        return ['tk' => $txt, 'type' => $tokenType, 'ln' => $line, 'cl' => $col, 'chPtr' => $chPtr];
    }

    /*******************************************************
     * The functions below are needed only for testing
     *******************************************************/

    public function showExpression(): string {
        $txtarray = explode("\r\n", $this->asciiExpression);
        $txt = '';
        foreach ($txtarray as $index => $subtext) {
            $txt.= ($index + 1)."\t".$subtext."\r\n";
        }
        return $txt;
    }

    private function blankPad(string $txt, int $length):string {
        while (strlen($txt) < $length) {
            $txt .= ' ';
        }
        return $txt;
    }

    public function showTokens():string {
        $txt = '';
        $tokens = [];
        while ($token = $this->getToken()) {
            $tokens[] = $token;
        }
        foreach($tokens as $index => $token) {
            $txt .= $index."\t".$this->blankPad($token['tk'], 10)."\t";
            $txt .= ' --'.$this->blankPad($token['type'], 10)."\t";
            $txt .= 'ln '.$token['ln'].' cl '.$token['cl']."\t";
            $txt .= 'chPtr '.$token['chPtr']."\r\n";
        }
        return $txt;
    }

    public function showErrors():string {
        if ($this->errtext != '') {
            return $this->errtext.' at position ln:'.$this->txtLine.', cl:'.$this->txtCol.', charPointer:'.$this->charPointer;
        }
        return '';
    }

    public function showSymbolTable():string {
        $txt = '';
        foreach($this->symbolTable as $index => $symbol) {
            $txt .= $index."\t".$symbol['type']."\r\n";
        }
        return $txt;
    }
}