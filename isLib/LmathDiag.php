<?php
namespace isLib;

/**
 * Diagnostic tools
 * 
 * @author A. Brunnschweiler
 * @version 12.09.2024
 * @package isLib
 */
class LmathDiag {

    private const TAB = "\t";
    private const NL = "\r\n";
    private const BLANK_LINE = '                                                                                             ';
    private const SP = '  ';


    /**
     * Returns a <pre> formattable ascii text for asciiExpression. If the isMathException $ex is set,
     * it is used to add a caret as a pointer in an additional line below the line that caused the exception
     * 
     * @param string $asciiExpression 
     * @param mixed $ex 
     * @return string 
     */
    private function annotatedExpression(string $asciiExpression, mixed $ex = false):string {
        $result = '';
        $txtarray = explode(self::NL, $asciiExpression);
        foreach ($txtarray as $index => $txt) {
            if (trim($txt) != '') {
                $result .= ($index + 1).self::TAB.$txt.self::NL;
            }
            if ($ex && isset($ex->info['ln']) && isset($ex->info['cl'])) {
                // Add a line pointing to the error
                if ($ex->info['ln'] == $index + 1) {
                    $result .= '-'.self::TAB.substr(self::BLANK_LINE, 0, $ex->info['cl'] - 1).'^'.self::NL;
                }
            }
        }
        return $result;
    }

    private function trace(\Exception $ex):string {
        $trace = $ex->getTrace();
        $txt = '';
        foreach ($trace as $entry) {
            $fileParts = explode('/',$entry['file']);
            $txt .= $entry['line'].self::TAB.$this->blankPad($fileParts[count($fileParts) - 1], 30).self::TAB.$entry['function'].self::NL;
        }
        return $txt;
    }

    /**
     * Appends spaces to $txt until $length is reached
     * 
     * @param string $txt 
     * @param int $length 
     * @return string 
     */
    private function blankPad(string $txt, int $length):string {
        while (strlen($txt) < $length) {
            $txt .= ' ';
        }
        return $txt;
    }

    /**
     * Returns an array with info about submitting $asciiExpression to LasciiLexer
     * The key 'errors' holds a possibly empty string with an error message, if an error occurred
     * The key 'tokens' holds a <pre> formatted numbered list of tokens detected before the first lexer error
     * The key 'annotatedExpression' holds $asciiExpression with possibly one additional line with a caret,
     * pointing approximately to the place, where the lexer stopped because of an error
     * NOTE: Only lexer errors are errors. $asciiExpression can be mathematically wrong without producing a lexer error
     *  
     * @param string $asciiExpression 
     * @return array{errors:string, trace:string, tokens:string, annotatedExpression:string, symbols:string}
     */
    public function checkLexer(string $asciiExpression):array {
        $result = ['errors' => '', 'trace' => '', 'tokens' => '', 'annotatedExpression' => $this->annotatedExpression($asciiExpression), 'symbols' => ''];
        $LasciiLexer = new \isLib\LasciiLexer($asciiExpression);
        try {            
            $LasciiLexer->init();
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
            return $result;
        }
        try {
            $index = 0;
            while ($token = $LasciiLexer->getToken()) {
                $result['tokens'] .= $index.self::TAB.$this->blankPad($token['tk'], 10).self::TAB;
                $result['tokens'] .= ' --'.$this->blankPad($token['type'], 10).self::TAB;
                $result['tokens'] .= 'ln '.$token['ln'].' cl '.$token['cl'].self::TAB;
                $result['tokens'] .= 'chPtr '.$token['chPtr'].self::NL;
                $index++;
            }
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
            $result['annotatedExpression'] = $this->annotatedExpression($asciiExpression, $ex);
        }
        $symbolTable = $LasciiLexer->getSymbolTable();
        foreach($symbolTable as $index => $symbol) {
            $result['symbols'] .= $index.self::TAB.$symbol['type'].self::NL;
        }
        return $result;
    }

    private function space(int $level): string
    {
        $space = '';
        for ($i = 0; $i < $level; $i++) {
            $space .= self::SP;
        }
        return $space;
    }

    private function drawSubtree(string &$txt, array $node, int $level): void {
        if (isset($node['l'])) {
            $txt .= $this->drawSubtree($txt, $node['l'], $level + 1);
        }
        $txt .= $this->space($level) . $node['tk'] . ' ' . $node['type'] . self::NL;
        if (isset($node['r'])) {
            $txt .= $this->drawSubtree($txt, $node['r'], $level + 1);
        }
        if (isset($node['u'])) {
            $txt .= $this->drawSubtree($txt, $node['u'], $level + 1);
        }
    }

    private function drawParseTree(array $parseTree):string {
        $txt = '';
        if (empty($parseTree)) {
            $txt.= 'Parse tree is empty';
        } else {
            $this->drawSubtree($txt, $parseTree, 0);
        }
        return $txt;
    }

    private function drawVarNames(array $varNames):string {
        $txt = '';
        if (!empty($varNames)) {
            foreach ($varNames as $name) {
                $txt .= $name.self::NL;
            }
        }
        return $txt;
    }

    private function traversationList(array $traversation):string {
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

    /**
     * 
     * @param string $asciiExpression 
     * @return array{errors:string, trace:string, tokens:string, annotatedExpression:string, parseTree:string, variables:string} 
     */
    public function checkParser(string $asciiExpression):array {
        $result = ['errors' => '', 'trace' => '', 'annotatedExpression' => $this->annotatedExpression($asciiExpression), 'parseTree' => '', 'variables' => ''];
        $LasciiParser = new \isLib\LasciiParser($asciiExpression);
        try {            
            $LasciiParser->init();
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
            return $result;
        }
        try {
            $result['parseTree'] = $this->drawParseTree($LasciiParser->parse());
            $result['variables'] = $this->drawVarNames($LasciiParser->getVariableNames());
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
            $result['annotatedExpression'] = $this->annotatedExpression($asciiExpression, $ex);
        }
        $result ['traversation'] = $this->traversationList($LasciiParser->getTraversation());
        return $result;
    }

    public function checkEvaluator(array $parserTree, array $variables):array {
        $result = ['errors' => '', 'trace' => '', 'evaluation' => ''];
        $Levaluator = new \isLib\Levaluator($parserTree, $variables);
        try {
            $result['evaluation'] = $Levaluator->evaluate();
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
        }
        return $result;
    }

    public function checkLatex(array $parseTree):array {
        try {
            $Llatex = new \isLib\Llatex($parseTree);
            $result['latex'] = $Llatex->getLateX();
        } catch (\isLib\isMathException $ex) {
            $result['errors'] = $ex->getMessage();
            $result['trace'] = $this->trace($ex);
        }
        return $result;
    }
}