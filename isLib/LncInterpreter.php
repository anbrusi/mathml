<?php

namespace isLib;

/**
 * Builds an array representing a nanoCas object from a command string, following this syntax
 * 
 *  command         -> oneVarCmd | twoVarCmd
 *  oneVarCmd       -> 'strToNn' '(' literal ')'
 *  twoVarCommand   ->
 *  literal         -> digit {digit}
 *  twoVarCmd       -> nnAdd '(' var ',' var ')' | nnMult '(' var ',' var ')'
 *  var             -> command | '$' varname
 *  varname         -> alpha {alpha}
 * 
 * The Vocabulary of the lexer is given by '(' | ')' | ',' | '$', alpha {alpha} | digit {digit} 
 * All characters, that are not part of the vocabulary are ignored. They are filtered out by $this->init
 * 
 * @package isLib
 */
class LncInterpreter {
       
    const NCT_NATNUMBERS = 1;
    const NCT_INTNUMBERS = 2;
    const NCT_RATNUMBERS = 3;
    const NCT_STRING = 4;

    private string $command = '';
    private int $pointer = 0;
    private int $length = 0;

    private string|false $tk;

    private \isLib\LncNaturalNumbers $LncNaturalNumbers;
    private \isLib\LncVarStore $LncVarStore;

    function __construct() {
        $this->LncNaturalNumbers = new \isLib\LncNaturalNumbers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncVarStore = new \isLib\LncVarStore();
    }

    private function init(string $command):void {
        $this->command = '';
        for ($i = 0; $i < strlen($command); $i++) {
            if ($this->isAlpha($command[$i]) || $this->isDigit($command[$i]) || $this->isSymbol($command[$i])) {
                $this->command .= $command[$i];
            }
        }
        $this->pointer = 0;
        $this->length = strlen($this->command);
        $this->nextToken();
    }

    private function isAlpha(string $ch):bool {
        $ch = strtolower($ch);
        return (ord($ch) >= ord('a') && ord($ch) <= ord('z'));
    }

    private function isDigit(string $ch):bool {
        return (ord($ch) >= ord('0') && ord($ch) <= ord('9'));
    }

    private function isSymbol(string $ch):bool {
        return in_array($ch, ['(', ')', ',' , '$']);
    }

    /**
     * Sets $this>tk to the next token if there is one, to false if there is none.
     * Advances $this->pointer to the first position beyond the last character of the token $this->tk.
     * After the last string token $this->pointer = strlen($this->command). 
     * The next call to $this->nextToken sets $this->tk to false and does not increase $this->pointer
     * 
     */
    private function nextToken():void {
        if ($this->pointer >= $this->length) {
            // Unexpected end of input
            $token = false;
        } elseif ($this->isSymbol($this->command[$this->pointer])) {
            switch ($this->command[$this->pointer]) {
                case '(':
                    $token = '(';
                    $this->pointer += 1;
                    break;
                case ')':
                    $token = ')';
                    $this->pointer += 1;
                    break;
                case ',':
                    $token = ',';
                    $this->pointer += 1;
                    break;
                case '$': 
                    $token = '$';
                    $this->pointer += 1;
                    break;
            }
        } elseif ($this->isAlpha($this->command[$this->pointer])) {
            $token = '';
            while ($this->pointer < $this->length && $this->isAlpha($this->command[$this->pointer])) {
                $token .= $this->command[$this->pointer];
                $this->pointer += 1;
            }
        } elseif ($this->isDigit($this->command[$this->pointer])) {
            $token = '';
            while ($this->pointer < $this->length && $this->isDigit($this->command[$this->pointer])) {
                $token .= $this->command[$this->pointer];
                $this->pointer += 1;
            }
        }
        $this->tk = $token;
    }

    /**
     * $this->pointer points to one character beyond of where the offense has been detected.
     * Returns a string with an error message, the processed part and the offending token
     * 
     * @return string 
     */
    private function positionTxt():string {
        $pointer = $this->pointer;
        $ante = substr($this->command, 0, $pointer - strlen($this->tk));
        return 'processed: |'.$ante.'...|  offending token: |'.$this->tk.'|';
    }

    private function throwMathEx(int $nr) {
        \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, $nr, ['errtxt' => $this->positionTxt()]);
    }

    private function oneVarCommand():array {
        switch ($this->tk) {
            case 'strToNn':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                if (!$this->isDigit($this->tk)) {
                    // Literal expected
                    $this->throwMathEx(5);
                }
                $literal = $this->tk;
                $this->nextToken(); // Digest literal
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_NATNUMBERS, 'value' => $this->LncNaturalNumbers->strToNn($literal)];
        }
    }

    private function twoVarCommand():array {
        $ncCommand = $this->tk; // This is the actual nanoCAS command e.g. 'nnAdd'
        $this->nextToken(); // Digest the actual command
        if ($this->tk != '(') {
            // Open parenthesis expected
            $this->throwMathEx(3);
        }
        $this->nextToken(); // Digest '('
        $var1 = $this->command();
        if ($this->tk != ',') {
            // Comma expected
            $this->throwMathEx(6);
        }
        $this->nextToken(); // Digest ','
        $var2 = $this->command();
        if ($this->tk != ')') {
            // Close parenthesis expected
            $this->throwMathEx(4);
        }
        $this->nextToken(); // Digest ')'
        switch ($ncCommand) {
            case 'nnAdd':
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncNaturalNumbers->nnAdd($var1['value'], $var2['value'])];
            case 'nnMult':
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncNaturalNumbers->nnMult($var1['value'], $var2['value'])];
            case 'nnSub':
                if ($this->LncNaturalNumbers->nnCmp($var1['value'], $var2['value']) == -1) {
                    // Minuend smaller than subtrahend
                    $this->throwMathEx(8);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncNaturalNumbers->nnSub($var1['value'], $var2['value'])];
            case 'nnDiv':
            case 'nnMod':
                if ($this->LncNaturalNumbers->nnIsZero($var2['value'])) {
                    // Zero divisor
                    $this->throwMathEx(9);
                }
                $divMod = $this->LncNaturalNumbers->nnDivMod($var1['value'], $var2['value']);
                if ($ncCommand == 'nnDiv') {
                    return ['type' => self::NCT_INTNUMBERS, 'value' => $divMod['quotient']];
                } else {
                    return ['type' => self::NCT_INTNUMBERS, 'value' => $divMod['remainder']];
                }
            case 'nnGCD':
                if ($this->LncNaturalNumbers->nnIsZero($var1['value']) || $this->LncNaturalNumbers->nnIsZero($var2['value'])) {
                    // No divisors of zero
                    $this->throwMathEx(10);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncNaturalNumbers->nnGCD($var1['value'], $var2['value'])];
            default:
                // Unknown command
                $this->throwMathEx(7);
        }
    }

    private function command():array {
        if ($this->tk === false) {
            // Empty command
            $this->throwMathEx(1);
        }
        if ($this->tk == '$') {
            // Digest '$'
            $this->nextToken();
            $varname = '$'.$this->tk;
            // Digest varname
            $this->nextToken();
            $varvalue = $this->LncVarStore->getVar($varname);
            if ($varvalue === null) {
                $this->throwMathEx(12);
            }
            return $varvalue;
        } elseif ($this->isAlpha($this->tk)) {
            if (in_array($this->tk, ['strToNn'])) {
                return $this->oneVarCommand();
            } elseif (in_array($this->tk, ['nnAdd', 'nnSub', 'nnMult', 'nnDiv', 'nnMod', 'nnGCD'])) {
                return $this->twoVarCommand();
            } else {
                // Command expected
                $this->throwMathEx(2);
            }
        } else {
            // Command expected
            $this->throwMathEx(2);
        }
    }

    public function cmdToNcObj(string $command):array {
        $this->init($command);
        return $this->command();
    }
}