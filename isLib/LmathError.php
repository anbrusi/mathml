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

    public const errors = [
        1001 => 'Initialization failed, possibly the expression is empty',
        1002 => 'Decimal part of number is missing',
        1003 => 'Scale missing after E in number'
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

    public function __construct($message, $code, $info, \Throwable $previous = null) {
        $this->info = $info;
        parent::__construct($message, $code, $previous);
    }

}