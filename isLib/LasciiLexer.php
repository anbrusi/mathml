<?php

namespace isLib;

class LasciiLexer {

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
     * Text describing the last error
     * 
     * @var string
     */
    private string $errtext = '';

    function __construct(string $input) {
        $this->input = str_split($input);
        $this->charPointer = 0;
        $this->txtLine = 1;
        $this->txtCol = 1;
        // Set the initially current character
        $this->getNextChar();
    }

    public function init():bool {
        return true;
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
     * When the lexer initializes $thi->char holds the first input character, that belongs to the alphabeth or false
     * $this->getNextChar retrieves the following character in the alphabet and places it in $this->getChar
     * The lexer makes use of $this->character and calls $this->nextChar when done to digest it.
     * 
     * @return string|false 
     */
    private function getNextChar():void {
        while ($this->charPointer < count($this->input) && !$this->inAlphabet($this->input[$this->charPointer]) )  {
            if ($this->input[$this->charPointer] == "\n") {
                $this->txtLine += 1;
                $this->txtCol = 0;
            }
            $this->charPointer += 1;
            $this->txtCol += 1;
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
            $txt = $this->readNum();
            $token = ['tk' => $txt];
        } elseif ($this->firstInMatop($this->char)) {
            $txt = $this->readMatop();
            $token = ['tk' => $txt];
        } elseif ($this->firstInCmpop($this->char)) {
            $txt = $this->readCmpop();
            $token = ['tk' => $txt];
        } elseif ($this->firstInParenthesis($this->char)) {
            $txt = $this->readParenthesis();
            $token = ['tk' => $txt];
        } else {
            $token = ['tk' => 'unimplemented: '.$this->char];
            $this->getNextChar();
        }
        return $token;
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
     * Returns true iff $char belongs to the mathml alfabeth, but is neither a dicit nor an alpha
     * 
     * @param string $char 
     * @return bool 
     */
    private function isSpecialChar(string $char):bool {
        return in_array($char, ['+', '-', '*', '/', '=', '<', '>', '.', '(', ')']);
    } 

    /**
     * Returns true iff $char is the first character of a mathematical operation (provision is made for multicharacter operations)
     * 
     * @param string $char 
     * @return bool 
     */
    private function firstInMatop(string $char):bool {
        return in_array($char, ['+', '-', '*', '/']);
    }

    /**
     * Returns true iff $char is the first character of a mathematical operation (provision is made for multicharacter operations)
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
    private function readNum():string {
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
        if (strtoupper($this->char) == 'E') {
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
        return $txt;
    }

    /**
     * Returns a string denoting a mathematical operator
     * 
     * @return string 
     */
    private function readMatop():string {
        $txt = $this->char;
        $this->getNextChar();
        return $txt;
    }

    /**
     * Returns a string denoting a parentesis
     * 
     * @return string 
     */
    private function readCmpop():string {
        $txt = $this->char;
        $this->getNextChar();
        if (in_array($txt, ['>', '<']) && $this->char == '=') {
            $txt .= $this->char;
            $this->getNextChar();
        }
        return $txt;
    }

    /**
     * Returns a string denoting a parentesis
     * 
     * @return string 
     */
    private function readParenthesis():string {
        $txt = $this->char;
        $this->getNextChar();
        return $txt;
    }

    /*******************************************************
     * The functions below are needed only for testing
     *******************************************************/

    public function showTokens():string {
        $txt = '';
        $tokens = [];
        while ($token = $this->getToken()) {
            $tokens[] = $token;
        }
        foreach($tokens as $index => $token) {
            $txt .= $index."\t".$token['tk']."\r\n";
        }
        return $txt;
    }

    public function showErrors():string {
        if ($this->errtext != '') {
            return $this->errtext.' at position ln:'.$this->txtLine.', cl:'.$this->txtCol.', charPointer:'.$this->charPointer;
        }
        return '';
    }
}