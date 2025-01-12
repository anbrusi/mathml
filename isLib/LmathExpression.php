<?php

namespace isLib;

class LmathExpression {

    /**
     * INPUT:
     *      An ASCII math expression or a mathml expression cnformat to the syntax of LasciiParser, passed to the constructor
     * 
     * OUTPUT:
     *      $this->getParseTree A syntax tree in form of a PHP array, as described in LasciiParser
     *      
     */

    /**
     * The syntax tree obtained by parsing the input expression
     * 
     * @var array
     */
    private array $parseTree = [];

    /**
     * Possibly empty array of variable names, obtained after successfully parsing the input expression
     * 
     * @var array
     */
    private array $variableNames = [];

    /**
     * $expression can be an ASCII math expression or a presentation mathml expression.
     * Type is registered in $this->expressionType as one of the self::EXT_xx costants
     * If it is not void, it must obey the Syntax of LasciiParser. If it does not an exception is thrown.
     * 
     * @param string $expression 
     * @return void 
     */
    function __construct(string $expression) {
        if (preg_match('/<math.*?<\/math>/' , $expression, $matches) == 1) {
            $expression = $matches[0];
            $LpresentationParser = new \isLib\LpresentationParser($expression);
            // Convert presentation mathml to ASCII
            $expression = $LpresentationParser->getAsciiOutput();
        }
        // If we get here $this->expression is ascii, either because it was originally ascii or 
        // because it was transformed to ascii
        // Try to parse it
        $LasciiParser = new \isLib\LasciiParser($expression);
        $LasciiParser->init();
        $this->parseTree = $LasciiParser->parse();
        $this->variableNames = $LasciiParser->getVariableNames();
    }

    /**
     * Returns the parse tree as an array
     * If LmathExpression can be successfully instantiated, the parse tree is available.
     * If no exception is thrown when calling new LmathExpression, the parse tree is available
     * 
     * @return array 
     */
    public function getParseTree():array {
        return $this->parseTree;
    }

}