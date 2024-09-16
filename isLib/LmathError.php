<?php

namespace isLib;

/**
 * Implements an error signalling facility for LasciiLexer, LasciiParser, Levaluator, LpresentationParser
 * 
 * @package isLib
 * @author A. Brunnschweiler
 * @version 11.09.2024
 */
class LmathError {
    public const ORI_LEXER = 1000;
    public const ORI_PARSER = 2000;

    public const errors = [
        // Lexer errors
        1001 => 'Initialization failed, possibly the expression is empty',
        1002 => 'Digit expected after "." in decimal part of number',
        1003 => 'Digit expected after "E" in scale part of number',
        // Parser errors
        2001 => 'Unexpected end of input in boolatom',
        2002 => 'boolatom or "!" expected',
        2003 => 'Left term in “|“ must be bool',
        3004 => 'Right term in “|“ must be bool',
        3005 => 'Left term in “&“ must be bool',
        3006 => 'Right term in “&“ must be bool',
        3007 => ') expected',
        3008 => 'Negation must be followed by a boolean',
        3009 => 'Left part of comparison must be float',
        3010 => 'Right part of comparison must be float',
        3011 => 'Unary minus can be applied only to float value',
        3012 => 'Left part of addop must be of float type',
        3013 => 'Right part of addop must be of float type',
        2014 => 'Left part of mulop must be of float type',
        2015 => 'Right part of mulop must be of float type',
        2016 => 'Base in power must be float',
        2017 => 'Exponent in power must be float',
        2018 => 'Unexpected end of input in expression',
        2019 => 'Atom or (boolexpression) expected',
        2020 => '( expected',
        2021 => ', expected',
        2022 => 'unimplemented number of arguments',
        2023 => 'mathconst, variable or function not in symbol table',
        2024 => 'Atom expected',
        2025 => 'Cannot get variable names. There is no parse tree'
    ];

    public static function setError(int $origin, int $number, array $info = []) {
        $code = $origin + $number;
        $message = self::errors[$code];
        if (!empty($info)) {
            $message .= '. info=[';
            foreach ($info as $key => $value) {
                $message .= $key. ' => '.$value.', ';
            }
            // Cut last comma and space
            $message = substr($message, 0, strlen($message) - 2);
            $message .=']';
        }
        throw new isMathException($message, $code, $info);
    }
}

class isMathException extends \Exception {

     /**
     * Array containing additional information about the exception
     * Possible Keys are 'ln' the line of the error, 'cl' the column of the error
     * 
     * @var array
     */
    public array $info = [];

    public function __construct($message, $code, $info = [], \Throwable $previous = null) {
        $this->info = $info;
        parent::__construct($message, $code, $previous);
    }

}