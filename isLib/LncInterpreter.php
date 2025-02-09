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

    private string|false $tk = '';

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
     * Sets $this>tk to the next token if there is one, to false if there is none
     * 
     */
    private function nextToken():void {
        if ($this->pointer > $this->length) {
            $this->tk = false;
            return;
        }
        $token = '';
        if ($this->isSymbol($this->command[$this->pointer])) {
            switch ($this->command[$this->pointer]) {
                case '(':
                    $token .= '(';
                    $this->pointer += 1;
                    break;
                case ')':
                    $token .= ')';
                    $this->pointer += 1;
                    break;
                case ',':
                    $token .= ',';
                    $this->pointer += 1;
                    break;
                case '$': 
                    $token .= '$';
                    $this->pointer += 1;
                    break;
            }
        } elseif ($this->isAlpha($this->command[$this->pointer])) {
            while ($this->pointer < $this->length && $this->isAlpha($this->command[$this->pointer])) {
                $token .= $this->command[$this->pointer];
                $this->pointer += 1;
            }
        } elseif ($this->isDigit($this->command[$this->pointer])) {
            while ($this->pointer < $this->length && $this->isDigit($this->command[$this->pointer])) {
                $token .= $this->command[$this->pointer];
                $this->pointer += 1;
            }
        }
        $this->tk = $token;
    }

    private function oneVarCommand():array {
        switch ($this->tk) {
            case 'strToNn':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 3);
                }
                $this->nextToken(); // Digest '('
                if (!$this->isDigit($this->tk)) {
                    // Literal expected
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 5);
                }
                $literal = $this->tk;
                $this->nextToken(); // Digest literal
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 4);
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
            \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 3);
        }
        $this->nextToken(); // Digest '('
        $var1 = $this->command();
        if ($this->tk != ',') {
            // Comma expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 6);
        }
        $this->nextToken(); // Digest ','
        $var2 = $this->command();
        if ($this->tk != ')') {
            // Close parenthesis expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 4);
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
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 8);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncNaturalNumbers->nnSub($var1['value'], $var2['value'])];
            default:
                // Unknown command
                \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 7);
        }
    }

    private function command():array {
        if ($this->tk === false) {
            // Empty command
            \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 1);
        }
        if ($this->tk == '$') {
            // Digest '$'
            $this->nextToken();
            $varname = '$'.$this->tk;
            // Digest varname
            $this->nextToken();
            return $this->LncVarStore->getVar($varname);
        } elseif ($this->isAlpha($this->tk)) {
            if (in_array($this->tk, ['strToNn'])) {
                return $this->oneVarCommand();
            } elseif (in_array($this->tk, ['nnAdd', 'nnSub', 'nnMult'])) {
                return $this->twoVarCommand();
            } else {
                // Command expected
                \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 2);
            }
        } else {
            // Command expected
            \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_INTERPRETER, 2);
        }
    }

    public function cmdToNcObj(string $command):array {
        $this->init($command);
        return $this->command();
    }
}