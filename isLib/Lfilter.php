<?php

namespace isLib;

class Lfilter {

    private string $editorContent = '';

    /**
     * An array of all formulas found in $this->content.
     * The entries are 'ascii' => string with the ASCII expression,
     * 'position' the position of the first character of the MathML expression, if the formula was MathML,
     * or the position of the first delimiter character, if the formula was ASCII
     * 'length' the lebgth of the formula including delimiters
     * 'origin' one of the strings 'ascii' or 'mathml'
     * 
     * If $this->asciiContent() has been called and no formulas have been found it is the empty array,
     * if it has not been called, it is null
     * 
     * @var array|null
     */
    private array|null $asciiContent = null;

    function __construct(string $editorContent) {
        $this->editorContent = $editorContent;
    }

    /**
     * Returns an array with entries for all MathML expressions. Each entry is an array with keys 'mathml', 'position' 'length' and 'origin'
     * 'mathml' is the MathML expression, 'position' the position of the first character of 'mathml' in $this->editorContent,
     * 'length' the length of the 'mathml' string, 'origin' is the string 'mathml'.
     * 
     * @return array 
     */
    private function extractMathml():array {
        $result = [];
        preg_match_all('/<math.*?<\/math>/', $this->editorContent, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $result[] = ['mathml' => $match[0], 'position' => $match[1], 'length' => strlen($match[0]), 'origin' => 'mathml'];
        }
        return $result;
    }

    /**
     * Returns an array with entries for all ASCII Math expressions. Each entry is an array with keys 'ascii', 'position' and 'length'
     * 'ascii' is the part of $this->content wrapped by '!!' excluding '!!' itself, 
     * 'position' is the position of the first '!' within $this->editorContent framing 'ascii'
     * 'length' is the length of the framed 'ascii' including the framing '!!', 'origin' is the string 'ascii'
     * 
     * Note that 'position' and length includes the delimiters '!!' while 'ascii' does not
     * 
     * @return array 
     */
    private function extractAscii():array {
        $result = [];
        preg_match_all('/!!.*?!!/', $this->editorContent, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $result[] = ['ascii' => substr($match[0], 2, strlen($match[0]) - 4), 'position' => $match[1], 'length' => strlen($match[0]), 'origin' => 'ascii'];
        }
        return $result;
    }

    private function turnMathmlIntoAscii(array $mathml):array {
        $result = [];
        foreach ($mathml as $element) {
            $LpresentationParser = new \isLib\LpresentationParser($element['mathml']);
            $result[] = ['ascii' => $LpresentationParser->getAsciiOutput(), 'position' => $element['position'], 'length' => $element['length'], 'origin' => 'mathml'];
        }
        return $result;
    }

    /**
     * Extracts all formulas from $this->editorContent
     */
    public function extractMathContent():void {
        $mathml = $this->extractMathml();
        $ascii = $this->extractAscii();
        $exMathml = $this->turnMathmlIntoAscii($mathml);
        // Merge originally Mathml and originally ascii into one ASCII array
        $mergedAscii = array_merge($ascii, $exMathml);
        // Order by position
        usort($mergedAscii, function($a, $b) {return $a['position'] - $b['position'];});
        $this->asciiContent = $mergedAscii;
    }

    /**
     * Returns an array with all the formulas in $this->editorContent
     * If there are no formulas an empty array is returned
     * 
     * @return array|null 
     */
    public function getMathContent():array {
        if ($this->asciiContent === null) {
            \isLib\LmathError::setError(\isLib\LmathError::ORI_FILTER, 1);
        }
        return $this->asciiContent;
    }

    public static function annotateFormula(string &$html, int $position, int $length, string $class) {
        $before = substr($html, 0, $position);
        $math = substr($html, $position, $length);
        $after = substr($html, $position + $length);
        $html = $before.'<div class="'.$class.'">'.$math.'</div>'.$after;
    }

    public static function evaluateAsciiFormula(string $ascii, array $variables, string $trigUnit):float {
        $LasciiParser = new \isLib\LasciiParser($ascii);
        $LasciiParser->init();
        $parseTree = $LasciiParser->parse();
        $Levaluator = new \isLib\Levaluator($parseTree, $variables, $trigUnit);
        return $Levaluator->evaluate();
    }
}