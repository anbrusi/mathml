<?php

namespace isLib;

/**
 * Splits an ascii expression into tokens. 
 * 
 * INPUT: an ASCII math expression passed in the constructor
 * 
 * OUTPUT: $this->init (initialization),
 *         $this->&getSymbolTable (returns a symbol table after completion of lexing), 
 *         $this->getToken (Returns the next token in $this->input or false if no token is available)
 * 
 * ERRORS:  Errors cause a \isLib\isMathException exception. These exceptions are raised by calling \isLib\LmathError::setError
 *          The array info has keys 'input' the parsed text, 'ln' and 'cl' for the line and column where the eror was detected 
 * 
 * Characters that do not belong to the alphabet are ignored for the building of tokens, but are considered for their position.
 * Ex:. 2a$b yields a token '2' and a token 'ab'
 * 
 * Each token is an array with the following keys:
 *      'type': 'unknown' | 'number' | 'variable' | 'mathconst' | 'function' | 'matop' | 'comma' | 'cmpop' | 'paren' | 'bracket' | 'boolop' | 'boolvalue'
 *      'restype': 'float' | 'bool' | 'unknown'
 *      'tk': the input symbol like 'sin', '+', ')', '17.9' etc. 
 *      'ln': the line (numbered from 1) holding the last character of the token
 *      'cl': the first column (numbered from 0) after the end of the token
 *      (EX.: '2 + 3'  The token '+' has cl=4 because 3 is in position 4 and the lexer detects the end of token + only when it encounters the digit 3,
 *      token '3' has position 5, although 5 is no longer part of the parsed string)
 *      'chPtr': the position that would be retrieved next, one position ahead of the current character
 * Type dependent keys are
 *      'args' for type 'function'  The value of this key is the number of arguments of functions
 *      'value' for type 'mathconst' The value of this key is the numeric value of the mathematical constant
 * 
 * Token input format
 * ==================
 * 
 * number               -> digit { digit } [ '.' digit { digit } ] [ 'E' [ '-' ] digit { digit } ]
 *                         Ex: 412.9 E-3  yelds 0.4129 upon evaluation
 * identifier           -> alpha { alpha } [ digit { digit} ]
 *                         alpha can be lower or upper case
 * variable             -> identifier which is not reserved
 * mathconst            -> 'e' | 'pi'
 * function             -> 'abs' | 'sqrt' | 'exp' | 'ln' | 'log' | 'sin' | 'cos' | 'tan' | 'asin' | 'acos' | 'atan' | 'rand' | 'max' | 'min'| 'round'
 * boolvalue            -> 'true' | 'false'
 * reserved identifier  -> mathconst | functio | boolvalue
 * matop                -> '+' | '-' | '*' | '/' | '^'
 * comma                -> ','
 * cmpop                -> '=' | '<>' | '>' | '>=' | '<' | '<='
 * paren                -> '(' | ')'
 * bracket              -> '[' | ']'
 * boolop               -> '&' | '|' | '!'
 *                         '!' is the boolean negation
 * 
 * @package isLib
 * @author A. Brunnschweiler
 * @version 11.09.2024
 */
class LasciiLexer {

    /**
     * The ASCII expression submitted to the lexer
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
     * Index in $input of the character, that will be retrieved next by $this->getChar,
     * one position after the position of the current character $this->char
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
     * Lines are numbered from 1
     * 
     * @var int
     */
    private int $txtLine;

    /**
     * The number of the column just after the accepted character $this->char.
     * At the end of input this column is not a column of the text, but the column just after the last text character
     * 
     * Columns are numbered from 0
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

    function __construct(string $asciiExpression) {
        $this->asciiExpression = $asciiExpression;
        $this->input = mb_str_split($this->asciiExpression);
        $this->charPointer = 0;
        $this->txtLine = 1;
        $this->txtCol = 0;
        $this->setReservedIdentifiers();
    }

    public function init():void {
        // Set the initially current character
        $this->getNextChar();
        if ($this->char === false) {
            // Initialization failed, possibly the expression is empty
            \isLib\LmathError::setError(\isLib\LmathError::ORI_LEXER, 1, ['input' => $this->asciiExpression, 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1]); 
        }
    }

    private function setReservedIdentifiers():void {
        $functionNames = ['abs', 'sqrt', 'exp', 'ln', 'log', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'rand', 'max', 'min', 'round']; 
        foreach ($functionNames as $name) {
            $this->symbolTable[$name] = ['type' => 'function', 'restype' => 'float', 'args' => 1];
        }
        // Functions with more than 1 variable
        $this->symbolTable['max']['args'] = 2;
        $this->symbolTable['min']['args'] = 2;
        $this->symbolTable['rand']['args'] = 2;
        $this->symbolTable['round']['args'] = 2;
        // Mathematical constants
        $this->symbolTable['e'] = ['type' => 'mathconst', 'restype' => 'float', 'value' => M_E];
        $this->symbolTable['pi'] = ['type' => 'mathconst', 'restype' => 'float', 'value' => M_PI];
        // Boolean values
        $this->symbolTable['true'] = ['type' => 'boolvalue', 'restype' => 'bool', 'value' => 'true'];
        $this->symbolTable['false'] = ['type' => 'boolvalue', 'restype' => 'bool', 'value' => 'false'];
    }

    /**
     * When the lexer initializes $this->char holds the first input character, that belongs to the alphabeth or false
     * $this->getNextChar retrieves the following character in the alphabet and places it in $this->char
     * The lexer makes use of $this->character and calls $this->nextChar when done to digest it.
     * 
     * @return string|false 
     */
    private function getNextChar():void {
        $newline = false;
        // Skip unwanted characters, but detect line change
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
        // We have reached an acceptable character
        if ($this->charPointer < count($this->input)) {
            $this->char = $this->input[$this->charPointer];
            $this->charPointer += 1;
        } else {
            $this->char = false;
        }
        $this->txtCol += 1;
    }

    /**
     * Returns the next token in $this->input or false if no token is available
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
        } elseif ($this->firstInBrackets($this->char)) {
            $token = $this->readBracket();
        } elseif ($this->firstInIdentifiers($this->char)) {
            $token = $this->readIdentifier();
        } elseif ($this->char == ',') {
            $token = $this->readComma();
        } else {
            $token = ['tk' => 'unimplemented: '.$this->char, 'type' => 'unknown', 'restype' => 'unknown',
                      'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
            $this->getNextChar();
        }
        return $token;
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
        return in_array($char, ['+', '-', '*', '/', '=', '<', '>', '.', '(', ')', '[', ']', '^', ',', '&', '|', '!']);
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
     * Returns true iff $char is the first character of a parentesis (provision is made for multicharacter parenthesis)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInBrackets(string $char):bool {
        return in_array($char, ['[', ']']);
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
     * readInt is entered only if $this->char is a digit. This is controlledd by the calling function
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
     * If readNum is entered, it is guaranteed that $this->char is a digit
     * 
     * @param string $firstChar 
     * @return array 
     */
    private function readNum():array {
        // Save line and column, since they are incremented by following readInt and getNextChar
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
            if (!$this->isDigit($this->char)) {
                \isLib\LmathError::setError(\isLib\LmathError::ORI_LEXER, 2, ['input' => $this->asciiExpression, 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1]);
            }
            $decpart = $this->readInt();
            $txt .= $decpart;
        }
        if ($this->char == 'E') {
            $txt .= $this->char;
            $hasEpart = true;
            // Save position
            $eLine = $this->txtLine;
            $eCol = $this->txtCol; 
            $this->getNextChar();
        }
        if ($hasEpart) {
            if ($this->char == '-') {
                $txt .= $this->char;
                $this->getNextChar();
            }
            if (!$this->isDigit($this->char)) {
                \isLib\LmathError::setError(\isLib\LmathError::ORI_LEXER, 3, ['input' => $this->asciiExpression, 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1]);
            }
            $scale = $this->readInt();
            $txt .= $scale;
        }
        return ['tk' => $txt, 'type' => 'number', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $chPtr];
    }

    /**
     * Returns a token denoting a mathematical operator
     * 
     * @return array 
     */
    private function readMatop():array {
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'matop', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

    /**
     * Returns a token denoting a boolean operator
     * 
     * @return array 
     */
    private function readBoolop():array {
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'boolop', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

    private function readComma():array {
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'comma', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

    /**
     * Returns a token denoting a comparison
     * 
     * @return array 
     */
    private function readCmpop():array {
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
        return ['tk' => $txt, 'type' => 'cmpop', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

    /**
     * Returns a token denoting a parentesis '(' or ')'
     * 
     * @return array 
     */
    private function readParenthesis():array {
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'paren', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

     /**
     * Returns a token denoting a bracket '[' or ']'
     * 
     * @return array 
     */
    private function readBracket():array {
        $txt = $this->char;
        $this->getNextChar();
        return ['tk' => $txt, 'type' => 'bracket', 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }

    /**
     * Returns a token for an identifier
     * If readIdentifier is entered, it is guaranteed, that $this->char is an alpha
     * 
     * @return array 
     */
    private function readIdentifier():array {
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
        return ['tk' => $txt, 'type' => $tokenType, 'ln' => $this->txtLine, 'cl' => $this->txtCol - 1, 'chPtr' => $this->charPointer];
    }
}