<?php

namespace isLib;

use Exception;

class LmathExpression
{

    /**
     * INPUT:
     *      An ASCII math expression or a mathml expression cnformat to the syntax of LasciiParser, passed to the constructor
     * 
     * OUTPUT:
     *      $this->getParseTree A syntax tree in form of a PHP array, as described in LasciiParser
     *      $this->getVariableNames A possibly empty numeric array of variable names
     *      $this->getAsciiExpression The ASCII form of input. Differs from input only if input was MathML
     *      
     */

    
    /**
     * Array built from HTML by the constructor for each mathematical expression contained in HTML
     * The values are arrays with an ascii representation of the expression at position 0 and its offset in HTML at position 1
     * @var array
     */
    private $asciiExpressions = [];

    /**
     * The mathML originals of $this->asciiExpressions, if they can be decoded. 
     * MathML is at position 0, the offset in the underlying HTML at position 1
     * 
     * @var array
     */
    private $mathmlExpressions = [];

    /**
     * $html either contains mathML expressions or consists only of paragraphs with an ascii expression each.
     * If any mathML is found, any ascii expression is ignored. The second possibility is considered only if no mathML is found
     * 
     * @param string $html 
     * @return void 
     */
    function __construct(string $html) // Old parameter $expression
    {        
        $this->asciiExpressions = $this->extractMathmlExpressions($html);
        if (empty($this->asciiExpressions)) {
            $this->asciiExpressions = $this->extractAsciiExpressions($html);
        }
    }

    /**
     * Scans $html for mathML expressions and returns an array, whose values are arrays with the ASCII equivalent at position 0
     * and the ofsset of the mathML at position 1
     * 
     * @param string $html 
     * @return array 
     */
    public function extractMathmlExpressions(string $html):array {
        $result = [];
        $nr = preg_match_all('/<math.*?<\/math>/', $html, $matches, PREG_OFFSET_CAPTURE);
        if ($nr > 0) {
            $LpresentationParser = new \isLib\LpresentationParser();
            foreach ($matches[0] as $match) {
                $mathml = $match[0];
                $offset = $match[1];
                try {
                    $ascii = $LpresentationParser->getAsciiOutput($mathml);
                    $result[] = [$ascii, $offset];
                    $this->mathmlExpressions[] = [$mathml, $offset];
                } catch(\Exception $ex) {
                    // Not decodable MathML
                    \isLib\LmathError::setError(\isLib\LmathError::ORI_MATH_EXPRESSION, 5, ['offset' => $offset]);
                }
            }
        }
        return $result;
    }

    /**
     * Scans $html for paragraph tags and returns an array, whose values are arrays with the paragraph content at position 0
     * and the offset of this content at position 1
     * 
     * @param string $html 
     * @return array 
     */
    public function extractAsciiExpressions(string $html):array {
        $result = [];
        $nr = preg_match_all('/<p>(.+?)<\/p>/', $html, $matches, PREG_OFFSET_CAPTURE);
        if ($nr > 0) {
            foreach ($matches[1] as $match) {
                $txt = $match[0];
                $offset = $match[1];
                // remove all nonbreaking spaces
                $txt = str_replace('&nbsp;', '', $txt);
                $offset = $offset + strlen($match[0]) - strlen($txt);
                // remove ordinary white space
                $ltrimmed = ltrim($txt);
                $offset = $offset + strlen($txt) - strlen($ltrimmed);
                $txt = rtrim($ltrimmed);
                $result[] = [$txt, $offset];
            }
        }
        return $result;
    }

    public function getMathmlExpression(int $nr=0):array {
        if (!isset($this->mathmlExpressions[$nr])) {
            // Unknown MathML expression
            $LmathError = new \isLib\LmathError();
            $LmathError->setError(\isLib\LmathError::ORI_MATH_EXPRESSION, 4);
        }
        return $this->mathmlExpressions[$nr];
    }

    /**
     * Returns the ASCII expression $nr. Default is the first one
     * 
     * @param int $nr 
     * @return string 
     */
    public function getAsciiExpression(int $nr=0): string
    {
        if (!isset($this->asciiExpressions[$nr])) {
            // Unknown ASCII expression
            $LmathError = new \isLib\LmathError();
            $LmathError->setError(\isLib\LmathError::ORI_MATH_EXPRESSION,3);
        }
        return $this->asciiExpressions[$nr][0];
    }

    /**
     * Returns the offset in the original HTML of expression $nr.
     * This can be either the start of mathML or the start of a text inside a paragraph in case of $html without any mathML
     * 
     * @param int $nr 
     * @return int 
     * @throws isMathException 
     */
    public function getExpresionOffset(int $nr=0):int {
        if (!isset($this->asciiExpressions[$nr])) {
            // Unknown ASCII expression
            $LmathError = new \isLib\LmathError();
            $LmathError->setError(\isLib\LmathError::ORI_MATH_EXPRESSION,3);
        }
        return $this->asciiExpressions[$nr][1];
    }

    /**
     * Returns the parse tree of ascii expression $nr as an array
     * 
     * @return array 
     */
    public function getParseTree(int $nr=0): array
    {
        $asciiExpression = $this->getAsciiExpression($nr);
        $LasciiParser = new \isLib\LasciiParser($asciiExpression);
        $LasciiParser->init();
        $parseTree = $LasciiParser->parse();
        return $parseTree;
    }

    /**
     * Returns a possibly empty numeric array of parsed variable names for those variables, occuring in ascii expression $nr
     * 
     * @return array 
     */
    public function getVariableNames(int $nr=0): array
    {
        $asciiExpression = $this->getAsciiExpression($nr);
        $LasciiParser = new \isLib\LasciiParser($asciiExpression);
        $LasciiParser->init();
        $parseTree = $LasciiParser->parse();
        $varnames = $LasciiParser->getVariableNames();
        return $varnames;
    }

    /**
     * Scans $this->asciiExpressions for equal signs.
     * Transforms each equation into an equation, whose right side is zero and returns an array of parse trees of the left sides
     * 
     * @return array 
     * @throws isMathException 
     * @throws Exception 
     */
    public function getEquations():array {
        $equations = [];
        $nrExpressions = count($this->asciiExpressions);
        for ($i = 0; $i < $nrExpressions; $i++) {
            $asciiExpression = $this->getAsciiExpression($i);
            // Replace equation by zero difference
            $parts = explode('=', $asciiExpression);
            if (count($parts) == 2) {
                $asciiExpression = $parts[0].'-('.$parts[1].')';
            }
            $LasciiParser = new \isLib\LasciiParser($asciiExpression);
            $LasciiParser->init();
            $parseTree = $LasciiParser->parse();
            $equations[] = $parseTree;
        }
        return $equations;
    }
}
