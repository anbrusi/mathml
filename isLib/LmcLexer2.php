<?php
/**
 * The purpose of this lexer is to tokenize strings for the syntax defined in LmcParser:
 * 
 * The lexer accepts a string and returns an array of tokens, one for each terminal symbol of the syntax
 * and one for sequences of digits and one for each variable.
 * If $this->oneCharVariables is true, variables are all lowercase letters except e
 * If $this->oneCharVariables is true, variables are sequences of lowercase letters, that do not match a terminal symbol
 * 
 * Terminal symbols are:
 * ---------------------
 * // caret
 * '^', 
 * 
 * // mulop
 * '**', '/', '?', '*', '&',
 * 
 * // addop
 * '+', '-', '|',
 * 
 * // compareop
 * '=', '>=', '>', '<=', '<>', '<',
 * 
 * // brackets
 * '(', ')', '[', ']', '{', '}',
 * 
 * // separators
 * '.', ',', 'E',
 * 
 * // boolval
 * 'true', 'false',
 * 
 * // mathconst
 * 'pi', 'e',
 * 
 * // functions
 * 'sqrt', 'exp', 'ln', 'log', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'rnd', 'max', 'min',
 * 
 * // constructors
 * "matrix", "array", "vector"
 * 
 * Other token generating symols:
 * ------------------------------
 * <integer = digit sequence>, 
 * <variable  = letter sequence that is not one of the above | lowercase letter different from e (oneCharacter case)>
 * 
 *  - sequences of lowercase letters are first compared to terminal symbols.
 *  - If a match is achieved they yield the corresponding token, else they yield one or several variable tokens
 *    depending on $this->oneCharVariables
 * 
 * TOKEN NAMES
 * ===========
 * Tokens are arrays with keys 'tk', 'startpos'
 * 'tk' is the name of the token. It is one of the terminal symbols or 'integer' or 'variable'
 * 
 * IMPLICIT MULTIPLICATION
 * =======================
 * The method insertImplicitMultiplications accepts an array of tokens and returns anaugmented array of tokens.
 * It inserts implicit multiplications "?" between tokens, following these rules:
 * 
 * 
 * - mathconst followed by mathconst
 * - mathconst followed by number
 * - mathconst followed by variable
 * - mathconst followed by left parentheses
 * - mathconst followed by function
 * 
 * - number followed by mathconst
 * - number followed by variable
 * - number followed by left parentheses
 * - number followed by function
 * 
 * - right parentheses followed by a mathconst
 * - right parentheses followed by a number
 * - right parentheses followed by a left parentheses
 * - right parentheses followed by a variable
 * - right parentheses followed by a function
 * 
 * - variable followed by a left parentheses
 * - variable followed by a number
 *
 * In case of single char variables there are two more cases
 *
 * - variable followed by a variable
 * - variable followed by a mathconst
 * 
 * The lexer can be used for any syntax having the same symbols
 * 
 * 
 * *********************************************************************************************************************************
 * 
 * MicroCAS Syntax 10.09.2022 (Copy from LmcParser)
 * ================================================
 * 
 *	- This version takes care only of operator precedence: 1. "^", 2. mulop, 3. addop, 4. compareop
 *	- There are no negative numbers, they are implemented via unary minus node or via subtraction
 *	- block is not a real production, it is just the start point.
 *	- Inconsistent operations such as sum of number valued and boolean valued expressions
 *	  do not contradict the syntax. They are detected after construction of the parse tree.
 * ----------------------------------------------------------------------------------------------------------------------------------
 * EBNF
 * ====
 * 
 * block		-> comparison
 * comparison   -> expression [compareop expression]
 * compareop	-> "=" | ">" | ">=" | "<" | "<=" | "<>"
 * expression	-> ["-"] term {addop term}
 * addop		-> "+" | "-" | "|"
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
 * 
 * number is POSITIVE. There are no negative numbers in "mathexp". Where needed the operator self::N_UNARYMINUS is used.
 * 
 * "?" is the implicit multiplication operator, it is an alias of '*' as far as the syntax is concerned.
 * It can be inserted at the lexer level by LmcLexer::insertImplicitMultiplications to insert multiplications which
 * are not explicitly written in traditional math notation.
 * -----------------------------------------------------------------------------------------------------------------------------------
 * 
 * varParser
 * =========
 * 
 * The variable parser accepts an array of tokens as generated by LmcLexer and returns 
 * an array of variable values indexed by their names
 * 
 * MicroVAR Syntax 31.08.2022
 * ==========================
 * 
 * ----------------------------------------------------------------------------------------------------------------------------------
 * EBNF
 * ====
 * 
 * block		-> vardef {"," vardef}
 * vardef		-> varname "=" varvalue
 * varname		-> alpha {alpha}
 * alpha		-> -small ascii letter-
 * varvalue		-> comparison (with no variables)
 * 
 * 
 * 
 * @author A. Brunnschweiler
 * @version 10.09.2022
 */
namespace isLib;

use Error;

class LmcLexer2 {


	const TAB = '  ';
	const BR = "\r\n";

    /**
     * Since terminals are searched in the given order, it is essential that longer terminals 
     * come before shorter, if they have a common beginning. Ex: '<=' before '<' search must be greedy.
     */
    const TERMINAL_SYMBOLS = array (
        // caret
        '^', 
        
        // mulop
        '**', '/', '?', '*', '&',
        
        // addop
        '+', '-', '|',
        
        // compareop
        '=', '>=', '>', '<=', '<>', '<',
        
        // brackets
        '(', ')', '[', ']', '{', '}',
        
        // separators
        '.', ',', 'E',
        
        // boolval
        'true', 'false',
        
        // mathconst
        'pi', 'e',
        
        // functions
        'abs', 'sqrt', 'exp', 'ln', 'log', 'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'rnd', 'max', 'min',
        
        // constructors
        'matrix', 'array', 'vector'
    );

    
    /**
     * Lexer errors. Their numeric value is in the range 1-99
     */
	const L_ERR_NOT_ASCII = 1; // The mathexp string submitted to the lexer contains non ASCII characters
	const L_ERR_NO_INPUT = 2; // The mathexp string submitted to the lexer is empty. The check is made after eliminating blank space
	const L_ERR_PREMATURE_END = 3; // Premature end of the mathexp string
	const L_ERR_NUMBER_FORMAT = 4; // Error in number format
	const L_ERR_ILLEGAL_CHAR = 5; // Illegal character found by lexer. The character found cannot be associated to a token.

    /**
     * The number of decimals, to which floatinpoint numbers are rounded
     * 
     * @var int
     */
    private $rounding = 4;

    /**
     * Set by the constructor. True if variable names are restricted to one character length
     * 
     * @var bool
     */
    private $oneCharVariables;


	/**
	 * If the lexer detects a new variable name it adds it to this array. 
	 * No duplicates are added
	 * 
	 * @var array
	 */
	protected $variableNames = array();

	/**
	 * The input to the lexer after elimination of all white space
	 * 
	 * @var string|null
	 */
	private $cleanMathexp = null;

    /**
     * Is initialized to null by the lexer and set to the position, where an error occurred if the lexer stops on error.
     * 
     * @var int|null
     */
    private $errpos;

     /**
	 * If $oneCharVariable is true variable names can only be a single lowercase letter.
	 * This allows to insert implicit multiplications between letters.
	 * If multiletter Variables are allowed, multiplication between two variables must be explicit.
	 *
	 * @param bool $oneCharVariables
	 */
	function __construct(bool $oneCharVariables) {
		$this->oneCharVariables = $oneCharVariables;
	}

     /**
      * Accepts a string with the listed terminal symbols, sequences of digits and sequences of lowercase characters
      * Returns an array of tokens
	  *
      * @param string $mathexp 
      * @return array 
      */
	public function lexer(string $mathexp):array {
		$tokens = array();
		$this->errpos = null;
		// Get rid of any blank space
		$mathexp = preg_replace('/\s/', '', $mathexp);
		// Store the clean version of $mathexp for further reference
		$this->cleanMathexp = $mathexp;
        $not_ascii = $this->check_ascii($mathexp);
		if (is_int($not_ascii)) {
            $this->errpos = $not_ascii;
			$this->error(self::L_ERR_NOT_ASCII);
		}
		$length = strlen($mathexp);
		if ($length == 0) {
			$this->errpos = 0;
			$this->error(self::L_ERR_NO_INPUT);
		}
		$i = 0;
		while ($i < $length) {
            $found = false;
            // Check terminal symbols
            foreach (self::TERMINAL_SYMBOLS as $symbol) {
                $pos = strpos($mathexp, $symbol, $i);
                if ($pos === $i) {
                    // Detected a terminal, build the token
                    $token = array('tk' => $symbol, 'startpos' => $i);
                    $tokens[] = $token;
                    // Adjust the posiion
                    $i += strlen($symbol);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Try a sequence of digits
                $startpos = $i;
                $buffer = '';
                while ($i < $length && $this->isDigit($mathexp[$i])) {
                    $buffer .= $mathexp[$i];
                    $i++;
                }
                if (strlen($buffer) > 0) {
                    // We got a sequence of digits
                    $token = array('tk' => 'integer', 'value' => $buffer, 'startpos' => $startpos);
                    $tokens[] = $token;
                    $found = true;
                } 
            }
            if (!$found) {
                // Try one or more variables
                $startpos = $i;
                $buffer = '';
                while ($i < $length && $this->isLowAlpha($mathexp[$i])) {
                    $buffer .= $mathexp[$i];
                    $i++;
                }
                if (strlen($buffer) > 0) {
                    // We got a sequence of digits
                    if ($this->oneCharVariables) {
                        for ($j = 0; $j < strlen($buffer); $j++) {
                            $token = array('tk' => 'variable', 'value' => $buffer[$j], 'startpos' => $startpos);
                            $tokens[] = $token;
                            $this->variableNames[] = $buffer[$j];
                        }
                        $found = true;
                    } else {
                        $token = array('tk' => 'variable', 'value' => $buffer, 'startpos' => $startpos);
                        $tokens[] = $token;
                        $this->variableNames[] = $buffer;
                        $found = true;
                    }
                }
            }
            if (!$found) {
                $this->errpos = $i;
                $this->error(self::L_ERR_ILLEGAL_CHAR);
            }
        }
		return $tokens;
	}
	

	 /**
	  * Implicit multiplications, denoted by '?' are inserted at lexer level in the token array.
	  * Conditions for implicit multiplication are:
	  *
	  * - mathconst followed by mathconst
	  * - mathconst followed by number
	  * - mathconst followed by variable
	  * - mathconst followed by left parentheses
	  * - mathconst followed by function
	  * 
	  * - number followed by mathconst
	  * - number followed by variable
	  * - number followed by left parentheses
	  * - number followed by function
	  * 
	  * - right parentheses followed by a mathconst
	  * - right parentheses followed by a number
	  * - right parentheses followed by a left parentheses
	  * - right parentheses followed by a variable
	  * - right parentheses followed by a function
	  * 
	  * - variable followed by a left parentheses
	  * - variable followed by a number
	  *
	  * In case of single char variables there are two more cases
	  *
	  * - variable followed by a variable
	  * - variable followed by a mathconst
	  *
	  * @return array
	  */
	  public function insertImplicitMultiplications(array $tokens):array {
		$i = 0;
		$nrTokens = count($tokens);
		$newtokens = array();
		while ($i < $nrTokens) {
			switch ($tokens[$i]['tk']) {
				// mathconst followed by ...
				case 'pi':
				case 'e':
					$newtokens[] = $tokens[$i];
					if (($i + 1 < $nrTokens) && (
							$this->isMathconst($tokens[$i + 1]['tk']) ||
							($tokens[$i + 1]['tk'] == 'integer') ||
							($tokens[$i + 1]['tk'] == 'variable') ||
							($tokens[$i + 1]['tk'] == '(') ||
							$this->isFunction($tokens[$i + 1]['tk']))) {
						// Implicit multiplication number followed by variable, left parentheses, function
						$newtokens[] = array('tk' => '?', 'startpos' => -1);
					}
					$i++;
					break;
				// number followed by ...
				case 'integer':
					$newtokens[] = $tokens[$i];
					if (($i + 1 < $nrTokens) && (
							$this->isMathconst($tokens[$i + 1]['tk']) ||
							($tokens[$i + 1]['tk'] == 'variable') ||
							($tokens[$i + 1]['tk'] == '(') ||
							$this->isFunction($tokens[$i + 1]['tk']))) {
						// Implicit multiplication number followed by variable, left parentheses, function
						$newtokens[] = array('tk' => '?', 'startpos' => -1);
					}
					$i++;
					break;
				// right parentheses followed by ...
				case ')':
					$newtokens[] = $tokens[$i];
					if (($i + 1 < $nrTokens) && (
							$this->isMathconst($tokens[$i + 1]['tk']) ||
							($tokens[$i + 1]['tk'] == 'integer') ||
							($tokens[$i + 1]['tk'] == '(') ||
							($tokens[$i + 1]['tk'] == 'variable') ||
							$this->isFunction($tokens[$i + 1]['tk']))) {
						// Implicit multiplication right parentheses followed by number, left parentheses, variable, function
						$newtokens[] = array('tk' => '?', 'startpos' => -1);
					}
					$i++;
					break;
				case 'variable':
					$newtokens[] = $tokens[$i];
					if (($i + 1 < $nrTokens) && (
							$this->isMathconst($tokens[$i + 1]['tk']) ||
							($tokens[$i + 1]['tk'] == '(') ||
							($tokens[$i +1]['tk'] == 'integer'))) {
						// Implicit multiplication between variable and number or variable and parenthesized expression
						$newtokens[] = array('tk' => '?', 'startpos' => -1);
					}
					if ($this->oneCharVariables) {
						// In case of single char variables, there is an implicit multiplication between two letters
						if (($i + 1 < $nrTokens) && (($tokens[$i + 1]['tk'] == 'variable'))) {
							// Implicit multiplication between variable and variable
							$newtokens[] = array('tk' => '?', 'startpos' => -1);
						}
					}
					$i++;
					break;
				default:
					$newtokens[] = $tokens[$i];
					$i++;
			}
		}
		return $newtokens;
	}
	
	/**
	 * Checks if $string consists of ascii characters only
	 *
	 * @param string $string
	 * @return bool
	 */
	private function is_ascii(string $string):bool {
		return ( bool ) ! preg_match( '/[\\x80-\\xff]+/' , $string );
	}
	 
    /**
     * Returns true if all characters in $string are ascii,
     * the position of the first non-ascii character else
     * 
     * @param string $string 
     * @return bool|int 
     */
    private function check_ascii(string $string):bool|int {
        $matches = array();
        $match = preg_match( '/([\\x80-\\xff])/' , $string, $matches);
        if ($match === 0) {
            return true;
        } elseif ($match === false) {
            throw new \Error('Error checking ASCII');
        } else {
            return strpos($string, $matches[1]);
        }
    }
	/**
	 * Returns true if $ch is a character between 'a' and 'z', false else
	 *
	 * @param string $ch
	 * @return bool
	 */
	private function isLowAlpha($ch):bool {
		return ((ord($ch) >= 97) && (ord($ch) <= 122));
	}

	/**
	 * Returns true if $ch is a character between '0' and '9', false else
	 *
	 * @param string $ch
	 * @return bool
	 */
	 private function isDigit($ch):bool {
		return ((ord($ch) >= 48) && (ord($ch) <= 57));
	 }

	 /**
	  * Checks if $token is a mathematical constant 
	  * 
	  * @param int $token
	  * @return bool
	  */
	 protected function isMathconst(string $token):bool {
	 	return $token == 'e' || $token == 'pi';
	 }

	 /**
	  * Checks if $token is a compareop
	  * 
	  * @param int $token
	  * @return bool
	  */
	 protected function isCompareop(string $token):bool {
	 	if (in_array($token, array('=', '>=', '>', '<=', '<>', '<'))) {
	 		return true;
	 	}
	 	return false;
	 }
	
	 /**
	  * Checks if $token is a function
	  * 
	  * @param int $token
	  * @return bool
	  */
	  private function isFunction(string $token):bool {
		if (in_array($token, array('abs', 'sqrt', 'exp', 'ln', 'log', 'sin', 'cos', 'tan', 
								   'asin', 'acos', 'atan', 'rnd', 'max', 'min'))) {
			return true;
		}
		return false;
	}

	 /**
	  * The buffer is a string of digits and possibly a decimal point.
	  * Return value is again a string representing the number in $buffer rounded to $this->rounding decimal places
	  *
	  * @param string $buffer
	  * @return string the corrected buffer
	  */
      private function stringRound(string $buffer): string {
        $roundedFloat = round(floatval($buffer),$this->rounding);
        $roundedString = strval($roundedFloat);
        return $roundedString;
    }

    /**
	 * Returns the names of error code constants from their error code
	 *
	 * @param int $errcode
	 * @return string
	 */
	protected function errorName(int $errcode):string {
		switch ($errcode) {
			case 1:
				return 'L_ERR_NOT_ASCII';
			case 2:
				return 'L_ERR_NO_INPUT';
			case 3:
				return 'L_ERR_PREMATURE_END';
            case 4:
                return 'L_ERR_NUMBER_FORMAT';
			case 5:
				return 'L_ERR_ILLEGAL_CHAR';
			default:
				return 'Unknown error '.$errcode;
		}
	}
	
    /**
	 * Throws an exception with code = $errCode
	 * The text is 'LmcLexer error <name of $errcode>'. If there is an addendum ': <content of addendum>' is appended
	 * 
	 * @param int $errCode
	 * @throws \Exception
	 */
	protected function error(int $errCode, $addendum = '') {
		$txt = 'LmcLexer error '.$this->errorName($errCode);
        $txt .= ' after: '.substr($this->cleanMathexp, 0, $this->errpos);
		if ($addendum != '') {
			$txt .= ': '.$addendum;
		}
		throw new \Exception($txt, $errCode);
	}

	/**
	 * Returns an array with the names of all detected variables in alphabetic order
	 * 
	 * @return array 
	 */
	public function getVariableNames():array {
		sort($this->variableNames);
		return $this->variableNames;
	}

	/***************************************
	 * Service methods
	 ***************************************/

	/**
	 * Returns the token array $tokens as a text string suitable for <pre> formatting
	 * 
	 * @return string
	 */
	public function getTokensAsTxt(array $tokens):string {
		$txt = '';
		foreach ($tokens as $token) {
			$txt .= $token['tk'];
			if (isset($token['value'])) {
				$txt .= ' ['.$token['value'].']';
			}
			$txt .= '  position: '.$token['startpos'];
			$txt .= self::BR;
		}
		return $txt;
	}
}