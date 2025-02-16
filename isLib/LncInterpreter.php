<?php

namespace isLib;

/**
 * Builds an array representing a nanoCas object from a command string, following this syntax
 * 
 *  command         -> oneVarCmd | twoVarCmd
 *  oneVarCmd       -> 'strToNn' '(' natliteral ')' | nnToStr '(' var ')'
 *                      strToInt' '(' intliteral ')' | 'intAbs' '(' var ')' | intToStr '(' var ')'
 *                     'strToRn' '(' ratliteral ')' | 'rnToStr' |
 *                     'strToRp' '(' rpliteral ')'
 *  natliteral      -> digits
 *  intLiteral      -> ['-'] digits
 *  ratliteral      -> ['-'] digits '/' ['-'] digits
 *  rpliteral       -> ['-'] monomial {addop monomial}
 *  monomial        -> [digits] ['/' digits] [power]
 *  power           -> 'x' ['^' natliteral] 
 *  digits          -> digit {digit}
 *  twoVarCommand   -> twoVarFct '(' var ',' var ')'
 *  twoVarFct       -> 'nnAdd' | 'nnSub' | 'nnMult' | 'nnDiv' | 'nnMod' | 'nnGCD' | 
 *                     'intAdd' | 'intSub' | 'intMult' | 'int∂iv' | 'intMod' |
 *                     'rnAdd' | 'rnSub' | 'rnMulrt' | 'rnDiv' | 'rnPower'
 *  var             -> command | '$' varname
 *  varname         -> alphas
 *  alphas          -> alpha {alpha}
 * 
 * The Vocabulary of the lexer is given by '(' | ')' | ',' | '$' | '-' | '/' | '+' | '^' | alphas | digits 
 * All characters, that are not part of the vocabulary are ignored. They are filtered out by $this->init
 * 
 * @package isLib
 */
class LncInterpreter {
       
    const NCT_NATNUMBERS = 1;
    const NCT_INTNUMBERS = 2;
    const NCT_RATNUMBERS = 3;
    const NCT_STRING = 4;
    const NCT_RATPOLYNOMIALS = 5;

    private string $command = '';
    private int $pointer = 0;
    private int $length = 0;

    private string|false $tk;

    private \isLib\LncNaturalNumbers $LncNaturalNumbers;
    private \isLib\LncIntegers $LncIntegers;
    private \isLib\LncRationalNumbers $LncRationalNumbers; 
    private \isLib\LncRatPolynomials $LncRatPolynomials;
    private \isLib\LncVarStore $LncVarStore;

    function __construct() {
        $this->LncNaturalNumbers = new \isLib\LncNaturalNumbers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncIntegers = new \isLib\LncIntegers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncRationalNumbers = new \isLib\LncRationalNumbers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncRatPolynomials = new \isLib\LncRatPolynomials(\isLib\Lconfig::CF_NC_RADIX);
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

    /**
     * Checks that the first character in $ch is an alpha
     * Note that ord acts on the first character of a string
     * 
     * @param string $ch 
     * @return bool 
     */
    private function isAlpha(string $ch):bool {
        if (strlen($ch) == 0) {
            return false;
        }
        $ch = strtolower($ch);
        return (ord($ch) >= ord('a') && ord($ch) <= ord('z'));
    }

    private function isDigit(string $ch):bool {
        if (strlen($ch) == 0) {
            return false;
        }
        return (ord($ch) >= ord('0') && ord($ch) <= ord('9'));
    }

    private function isSymbol(string $ch):bool {
        return in_array($ch, ['(', ')', ',' , '$', '-', '/', '+', '^']);
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
            $token = $this->command[$this->pointer];
            $this->pointer += 1;
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

    private function monomial():string {
        $monomial = '';
        if ($this->isDigit($this->tk)) {
            $monomial .= $this->tk;
            $this->nextToken();
            if ($this->tk == '/') {
                $monomial .= '/';
                $this->nextToken();
                if (!$this->isDigit($this->tk)) {
                    // Digits expected
                    $this->throwMathEx(17);
                }
                $monomial .= $this->tk;
                $this->nextToken();
            } else {
                // Cast natural number to rational number
                $monomial .= '/1';
            }
        } else {
            $monomial .= '1/1';
        }
        if ($this->tk == 'x') {
            $monomial .= $this->tk;
            $this->nextToken();
            if ($this->tk == '^') {
                $monomial .= '^';
                $this->nextToken();
                if (!$this->isDigit($this->tk)) {
                    //  Digits expected
                    $this->throwMathEx(17);
                }
                $monomial .= $this->tk;
                $this->nextToken();
            } else {
                // Add implicit power 1
                $monomial .= '^1';
            }
        } else {
            // Add a fake power to the constant
            $monomial .= 'x^0';
        }
        return $monomial;
    }

    /**
     * A polynomial, following the syntax is representet the conventional way.
     * Ex: -x^7 + x^2 -4x^3+9/19
     * Coefficients can be omitted or can be rational numbers or natural numbers.
     * The monomial with the highest power must be the first and no two monomials can have the same power, but no order is required.
     * The output is a string in a normalized form
     * Ex: -1/1x^7+1/1x^2-4/1x^3+9/19x^0
     * All coefficients are rational numbers and are preceeded by a sign. All powers of x are explicit ( 0 and 1 included)
     * 
     * @return string 
     * @throws isMathException 
     */
    private function rpLiteral():string {
        $literal = '';
        if ($this->tk == '-') {
            $literal .= '-';
            $this->nextToken();
        } else {
            $literal .= '+';
        }
        $literal .= $this->monomial();
        while ($this->tk == '+' || $this->tk == '-') {
            $literal .= $this->tk;
            $this->nextToken();
            $literal .= $this->monomial();
        }
        return $literal;
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
            case 'nnToStr':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $var = $this->command();
                if ($var['type'] != self::NCT_NATNUMBERS) {
                    // CAS natural number expected
                    $this->throwMathEx(18);
                }
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_STRING, 'value' => $this->LncNaturalNumbers->nnToStr($var['value'])];
            case 'strToInt':
                $this->nextToken(); // Digest 'strToInt'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                if ($this->tk == '-') {
                    $literal = '-';
                    $this->nextToken();
                } else {
                    $literal = '';
                }
                if (!$this->isDigit($this->tk)) {
                    // Literal expected
                    $this->throwMathEx(5);
                }
                $literal = $literal.$this->tk;
                $this->nextToken(); // Digest literal
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncIntegers->strToInt($literal)];
            case 'intAbs':
                $this->nextToken(); // Digest 'intAbs'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $var = $this->command();
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                // Works as well, if $var is a natural number, so no check is needed
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncIntegers->intAbs($var['value'])];
            case 'intToStr':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $var = $this->command();
                if ($var['type'] != self::NCT_INTNUMBERS) {
                    // CAS integer expected
                    $this->throwMathEx(19);
                }
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_STRING, 'value' => $this->LncIntegers->intToStr($var['value'])];
            case 'strToRn':
                $this->nextToken(); // Digest 'strToRn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                if ($this->tk == '-') {
                    $literal = '-';
                    $this->nextToken();
                } else {
                    $literal = '';
                }
                if (!$this->isDigit($this->tk)) {
                    // Literal expected
                    $this->throwMathEx(5);
                }
                $literal .= $this->tk; 
                $this->nextToken(); // Digest literal
                if ($this->tk != '/') {
                    // Slash expected
                    $this->throwMathEx(14);
                }
                $literal .= $this->tk;
                $this->nextToken(); // Digest slash
                if ($this->tk == '-') {
                    $literal .= '-';
                    $this->nextToken(); // Digest -
                }
                if (!$this->isDigit($this->tk)) {
                    // Literal expected
                    $this->throwMathEx(5);
                }
                $literal .= $this->tk;
                $this->nextToken(); // Digest literal
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->strToRn($literal)];
            case 'rnToStr':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $var = $this->command();
                if ($var['type'] != self::NCT_RATNUMBERS) {
                    // CAS rational number expected
                    $this->throwMathEx(20);
                }
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_STRING, 'value' => $this->LncRationalNumbers->rnToStr($var['value'])];
            case 'strToRp':
                $this->nextToken(); // Digest 'strToRn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $literal = $this->rpLiteral();
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_RATPOLYNOMIALS, 'value' => $this->LncRatPolynomials->strToRp($literal)];
            case 'rpToStr':
                $this->nextToken(); // Digest 'strToNn'
                if ($this->tk != '(') {
                    // Open parenthesis expected
                    $this->throwMathEx(3);
                }
                $this->nextToken(); // Digest '('
                $var = $this->command();
                if ($var['type'] != self::NCT_RATPOLYNOMIALS) {
                    // CAS rational number expected
                    $this->throwMathEx(20);
                }
                if ($this->tk != ')') {
                    // Close parenthesis expected
                    $this->throwMathEx(4);
                }
                $this->nextToken(); // Digest ')'
                return ['type' => self::NCT_STRING, 'value' => $this->LncRatPolynomials->rpToStr($var['value'])];
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
                if ($var1['type'] != self::NCT_NATNUMBERS || $var2['type'] != self::NCT_NATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_NATNUMBERS, 'value' => $this->LncNaturalNumbers->nnAdd($var1['value'], $var2['value'])];
            case 'nnMult':
                if ($var1['type'] != self::NCT_NATNUMBERS || $var2['type'] != self::NCT_NATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_NATNUMBERS, 'value' => $this->LncNaturalNumbers->nnMult($var1['value'], $var2['value'])];
            case 'nnSub':
                if ($var1['type'] != self::NCT_NATNUMBERS || $var2['type'] != self::NCT_NATNUMBERS) {
                    $this->throwMathEx(13);
                }
                if ($this->LncNaturalNumbers->nnCmp($var1['value'], $var2['value']) == -1) {
                    // Minuend smaller than subtrahend
                    $this->throwMathEx(8);
                }
                return ['type' => self::NCT_NATNUMBERS, 'value' => $this->LncNaturalNumbers->nnSub($var1['value'], $var2['value'])];
            case 'nnDiv':
            case 'nnMod':
                if ($var1['type'] != self::NCT_NATNUMBERS || $var2['type'] != self::NCT_NATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                if ($this->LncNaturalNumbers->nnIsZero($var2['value'])) {
                    // Zero divisor
                    $this->throwMathEx(9);
                }
                $divMod = $this->LncNaturalNumbers->nnDivMod($var1['value'], $var2['value']);
                if ($ncCommand == 'nnDiv') {
                    return ['type' => self::NCT_NATNUMBERS, 'value' => $divMod['quotient']];
                } else {
                    return ['type' => self::NCT_NATNUMBERS, 'value' => $divMod['remainder']];
                }
            case 'nnGCD':
                if ($this->LncNaturalNumbers->nnIsZero($var1['value']) || $this->LncNaturalNumbers->nnIsZero($var2['value'])) {
                    // No divisors of zero
                    $this->throwMathEx(10);
                }
                return ['type' => self::NCT_NATNUMBERS, 'value' => $this->LncNaturalNumbers->nnGCD($var1['value'], $var2['value'])];
            case 'intAdd':
                if ($var1['type'] != self::NCT_INTNUMBERS || $var2['type'] != self::NCT_INTNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncIntegers->intAdd($var1['value'], $var2['value'])];
            case 'intSub':
                if ($var1['type'] != self::NCT_INTNUMBERS || $var2['type'] != self::NCT_INTNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncIntegers->intSub($var1['value'], $var2['value'])];
            case 'intMult':
                if ($var1['type'] != self::NCT_INTNUMBERS || $var2['type'] != self::NCT_INTNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_INTNUMBERS, 'value' => $this->LncIntegers->intMult($var1['value'], $var2['value'])];case 'nnDiv':
            case 'intDiv':
            case 'intMod':
                if ($var1['type'] != self::NCT_INTNUMBERS || $var2['type'] != self::NCT_INTNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                if ($this->LncNaturalNumbers->nnIsZero($var2['value'])) {
                    // Zero divisor
                    $this->throwMathEx(9);
                }
                $divMod = $this->LncIntegers->intDivMod($var1['value'], $var2['value']);
                if ($ncCommand == 'intDiv') {
                    return ['type' => self::NCT_INTNUMBERS, 'value' => $divMod['quotient']];
                } else {
                    return ['type' => self::NCT_INTNUMBERS, 'value' => $divMod['remainder']];
                }
            case 'rnAdd':
                if ($var1['type'] != self::NCT_RATNUMBERS || $var2['type'] != self::NCT_RATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->rnAdd($var1['value'], $var2['value'])];
            case 'rnSub':
                if ($var1['type'] != self::NCT_RATNUMBERS || $var2['type'] != self::NCT_RATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->rnSub($var1['value'], $var2['value'])];
            case 'rnMult':
                if ($var1['type'] != self::NCT_RATNUMBERS || $var2['type'] != self::NCT_RATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->rnMult($var1['value'], $var2['value'])];
            case 'rnDiv':
                if ($var1['type'] != self::NCT_RATNUMBERS || $var2['type'] != self::NCT_RATNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->rnDiv($var1['value'], $var2['value'])];
            case 'rnPower':
                if ($var1['type'] != self::NCT_RATNUMBERS || $var2['type'] != self::NCT_INTNUMBERS) {
                    // Wrong nanoCAS type
                    $this->throwMathEx(13);
                }
                // The exponent is limited to the radix
                if (abs($var2['value'][0] > 1)) {
                    $this->throwMathEx(15);
                }
                $machineInt = $var2['value'][1];
                if ($var2['value'][0] < 0) {
                    $machineInt = - $machineInt;
                }
                return ['type' => self::NCT_RATNUMBERS, 'value' => $this->LncRationalNumbers->rnPower($var1['value'], $machineInt)];
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
            if (in_array($this->tk, ['strToNn', 'nnToStr', 'strToInt', 'intAbs', 'intToStr', 'strToRn', 'rnToStr', 'strToRp', 'rpToStr'])) {
                return $this->oneVarCommand();
            } elseif (in_array($this->tk, ['nnAdd', 'nnSub', 'nnMult', 'nnDiv', 'nnMod', 'nnGCD',
                                           'intAdd', 'intSub', 'intMult', 'intDiv', 'intMod',
                                           'rnAdd', 'rnSub', 'rnMult', 'rnDiv', 'rnPower'])) {
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