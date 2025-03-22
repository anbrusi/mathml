<?php

namespace isLib;

use Exception;

class LmathDebug {

    private const TAB = "\t";
    private const NL = "\r\n";
    private const BLANK_LINE = '                                                                                             ';
    private const SP = '  ';

    /**
     * Appends spaces to $txt until $length is reached
     * 
     * @param string $txt 
     * @param int $length 
     * @return string 
     */
    private static function blankPad(string $txt, int $length):string {
        while (strlen($txt) < $length) {
            $txt .= ' ';
        }
        return $txt;
    }

    /**********************************************
     * Methods related to exception interpretation
     **********************************************/

     /**
      * Returns a trace of called functions, which is <pre> formattable.
      * Works on any exception
      *
      * @param Exception $ex 
      * @return string 
      */
     public static function trace(\Exception $ex):string {
        $trace = $ex->getTrace();
        $txt = '';
        foreach ($trace as $entry) {
            $fileParts = explode('/',$entry['file']);
            $txt .= $entry['line'].self::TAB.self::blankPad($fileParts[count($fileParts) - 1], 30).self::TAB.$entry['function'].self::NL;
        }
        return $txt;
     }

     /**
      * Returns one of the LmathErorr::ORI_xxx constants
      *      
      * @param isMathException $ex 
      * @return string 
      */
     public static function getErrorOrigin(\isLib\isMathException $ex):int {
        $code = $ex->getCode();
        if ($code > \isLib\LmathError::ORI_LEXER && $code < \isLib\LmathError::ORI_PARSER) {
            return \isLib\LmathError::ORI_LEXER;
        } elseif ($code < \isLib\LmathError::ORI_EVALUATOR) {
            return \isLib\LmathError::ORI_PARSER;
        } elseif ($code < \isLib\LmathError::ORI_LATEX) {
            return \isLib\LmathError::ORI_EVALUATOR;
        } elseif ($code < \isLib\LmathError::ORI_PRESENTATION_PARSER) {
            return \isLib\LmathError::ORI_LATEX;
        } elseif ($code < \isLib\LmathError::ORI_MATH_EXPRESSION) {
            return \isLib\LmathError::ORI_PRESENTATION_PARSER;
        } elseif ($code > \isLib\LmathError::ORI_MATH_EXPRESSION) {
            return \isLib\LmathError::ORI_MATH_EXPRESSION;
        } else {
            throw new \Exception('Cannot extract origin from isMathException', 0, $ex);
        }
     }

    /**
     * Works on LEXER and ASCII PARSER errors
     * ======================================
     * Returns a <pre> formattable text of the expression with a caret as a pointer 
     * in an additional line below the line that caused the exception
     * 
     * @param \isLib\isMathException $ex 
     * @return string 
     */
    public static function annotatedExpression(\isLib\isMathException $ex):string {
        $result = '';
        $txtarray = explode(self::NL, $ex->info['input']);
        foreach ($txtarray as $index => $txt) {
            if (trim($txt) != '') {
                $result .= ($index + 1).self::TAB.$txt.self::NL;
                // Add a line pointing to the error
                if ($ex->info['ln'] == $index + 1) {
                    $result .= '-'.self::TAB.substr(self::BLANK_LINE, 0, $ex->info['cl'] - 1).'^'.self::NL;
                }
            }
        }
        return $result;
    }

    /**
     * Returns a <pre> formattable list of lexer trokens.
     * If the lexer does not succeed a DEBUG error describing the lexer error is displayed in place of the token list.
     * 
     * @param isMathException $ex 
     * @return string 
     */
    public static function lexerTokens(\isLib\isMathException $ex):string {
        return self::tokenList($ex->info['input']);
    }

    public static function traversation(\isLib\isMathException $ex):string {
        $traversation = $ex->info['traversation'];
        $indent = 0;
        $list = '';
        foreach ($traversation as $entry) {
            if (isset($entry[0]) && $entry[0] == 'X') {
                $indent -= 1;
            }
            $tabs = '';
            for ($i = 0; $i < $indent; $i++) {
                $tabs .= self::TAB;
            }
            $list .= $tabs.$entry.self::NL;
            if (isset($entry[0]) && $entry[0] == 'E') {
                $indent += 1;
            }
        }
        return $list;
    }

    /*************************
     * Representation methods
     *************************/

    private static function space(int $level): string
    {
        $space = '';
        for ($i = 0; $i < $level; $i++) {
            $space .= self::SP;
        }
        return $space;
    }
 
    /**
     * Returns a <pre> formattable list of lexer trokens of an ASCII expression.
     * If the lexer does not succeed a DEBUG error describing the lexer error is displayed in place of the token list.
     * 
     * @param string $asciiExpression 
     * @return string 
     */
    public static function tokenList(string $asciiExpression):string {
        $result = '';
        try {
            $LasciiLexer = new \isLib\LasciiLexer($asciiExpression);
            $LasciiLexer->init();
            $index = 0;
            while ($token = $LasciiLexer->getToken()) {
                $result .= $index.self::TAB.self::blankPad($token['tk'], 10).self::TAB;
                $result .= ' --'.self::blankPad($token['type'], 10).self::TAB;
                $result .= 'ln '.$token['ln'].' cl '.$token['cl'].self::TAB;
                $result .= 'chPtr '.$token['chPtr'].self::NL;
                $index++;
            }
        } catch (\isLib\isMathException $ex2) {
            $result = 'DEBUG ERROR: '.$ex2->getMessage();
        }
        return $result;
    }

    /**
     * Returns a <pre> formatable tree generated from an array 'traversation' of method names 
     * encountered by the recursive descent in Lparser
     * 
     * @param array $traversation 
     * @return string 
     */
    public static function traversationList(array $traversation):string {
        $indent = 0;
        $list = '';
        foreach ($traversation as $entry) {
            if (isset($entry[0]) && $entry[0] == 'X') {
                $indent -= 1;
            }
            $tabs = '';
            for ($i = 0; $i < $indent; $i++) {
                $tabs .= self::TAB;
            }
            $list .= $tabs.$entry.self::NL;
            if (isset($entry[0]) && $entry[0] == 'E') {
                $indent += 1;
            }
        }
        return $list;
    }

    private static function drawSubtree(string &$txt, array $node, int $level): void {
        if (isset($node['l'])) {
            $txt .= self::drawSubtree($txt, $node['l'], $level + 1);
        }
        $tk = $node['tk'];
        if ($tk == '-' && isset($node['u'])) {
            $tk = '-(u)';
        }
        $txt .= self::space($level) . $tk . ' ' . $node['type'] . self::NL;
        if (isset($node['r'])) {
            $txt .= self::drawSubtree($txt, $node['r'], $level + 1);
        }
        if (isset($node['u'])) {
            $txt .= self::drawSubtree($txt, $node['u'], $level + 1);
        }
    }

    public static function drawSymbolTable(array $symbolTable): string {
        $result = '';
        foreach($symbolTable as $index => $symbol) {
            $result .= $index.self::TAB.$symbol['type'].self::NL;
        }
        return $result;
    }

    public static function drawParseTree(array $parseTree):string {
        $txt = '';
        if (empty($parseTree)) {
            $txt.= 'Parse tree is empty';
        } else {
            self::drawSubtree($txt, $parseTree, 0);
        }
        return $txt;
    }
}