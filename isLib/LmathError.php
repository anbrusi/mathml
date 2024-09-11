<?php

namespace isLib;

/**
 * Implements an error signalling facility for LasciiLexer, LasciiParser, Levaluator, LpresentationParser
 * 
 * @package isLib
 */
class LmathError {
    public const ORI_LEXER = 1000;

    public const errors = [
        1001 => 'Initialization failed, possibly the expression is empty',
        1002 => 'Decimal part of number is missing',
        1003 => 'Scale missing after E in number'
    ];

    public static function setError(int $origin, int $number, array $info = []) {
        $code = $origin + $number;
        throw new isMathException(self::errors[$code], $code, $info);
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

    public function __construct($message, $code, $info, \Throwable $previous = null) {
        $this->info = $info;
        parent::__construct($message, $code, $previous);
    }

}