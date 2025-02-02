<?php

namespace isLib;

class Lfilter {

    private string $editorContent = '';

    function __construct(string $editorContent) {
        $this->editorContent = $editorContent;
    }

    /**
     * Returns an array with entries for all MathML expressions. Each entry is an array with keys 'mathml', 'position' and 'length'
     * 
     * @return array 
     */
    private function extractMathml():array {
        $result = [];
        preg_match_all('/<math.*?<\/math>/', $this->editorContent, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $result[] = ['mathml' => $match[0], 'position' => $match[1], 'length' => strlen($match[0])];
        }
        return $result;
    }

    /**
     * Returns an array with entries for all ASCII Math expressions. Each entry is an array with keys 'ascii', 'position' and 'length'
     * Note that 'position' and length includes the delimiters '!!' while 'ascii' does not
     * 
     * @return array 
     */
    private function extractAscii():array {
        $result = [];
        preg_match_all('/!!.*?!!/', $this->editorContent, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $result[] = ['ascii' => substr($match[0], 2, strlen($match[0]) - 4), 'position' => $match[1], 'length' => strlen($match[0])];
        }
        return $result;
    }

    private function turnMathmlIntoAscii(array $mathml):array {
        $result = [];
        foreach ($mathml as $element) {
            $LpresentationParser = new \isLib\LpresentationParser($element['mathml']);
            $result[] = ['ascii' => $LpresentationParser->getAsciiOutput(), 'position' => $element['position'], 'length' => $element['length']];
        }
        return $result;
    }

    /**
     * Returns the editor content, with MathML replaced by ASCII wrapped by '//'
     * 
     * @return string 
     */
    public function asciiContent():string {
        $mathml = $this->extractMathml();
        $ascii = $this->extractAscii();
        $exMathml = $this->turnMathmlIntoAscii($mathml);
        // Merge originally Mathml and originally ascii into one ASCII array
        $mergedAscii = array_merge($ascii, $exMathml);
        // Order by position
        usort($mergedAscii, function($a, $b) {return $a['position'] - $b['position'];});
        $html = '';
        $html .= '<pre>';
        $html .= '<ul>';
        foreach ($mergedAscii as $expression) {
            $html .= '<li>'.$expression['position']."\t".$expression['length']."\t".$expression['ascii'].'</li>';
        }
        $html .= '</ul>';
        $html .= '</pre>';
        return $html;
    }
}