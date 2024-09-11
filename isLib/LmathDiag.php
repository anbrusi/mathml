<?php
namespace isLib;

class LmathDiag {

    private const TAB = "\t";
    private const NL = "\r\n";
    private const BLANK_LINE = '                                                                                           ';

    public string $errtext = '';
    public string $annotatedExpression = '';
    /**
     * This is an ascii text, listing all tokens, encountered up to the first lexer error
     * Call $this->getTokens() to fill this list
     * 
     * @var string
     */
    public string $tokenList = '';
    /**
     * This is an ascii text listing all reserved words (functions, variables, mathconstants)
     * 
     * @var string
     */
    public string $symbolList = '';

    public function getExpression(string $asciiExpression):void {
        $this->annotatedExpression = '';
        // Expression without annotation
        $txtarray = explode(self::NL, $asciiExpression);
        // Try to parse it, detcting error positions
        $LasciiParser = new \isLib\LasciiParser($asciiExpression);
        try {
            $LasciiParser->init();
            $LasciiParser->parse();
        } catch (isMathException $ex) {
            foreach ($txtarray as $index => $txt) {
                if (trim($txt) != '') {
                    $this->annotatedExpression .= ($index + 1).self::TAB.$txt.self::NL;
                }
                if (isset($ex->info['ln']) && isset($ex->info['cl'])) {
                    // Add a line pointing to the error
                    if ($ex->info['ln'] == $index + 1) {
                        $this->annotatedExpression .= '-'.self::TAB.substr(self::BLANK_LINE, 0, $ex->info['cl'] - 1).'^'.self::NL;
                    }
                }
            }
            // Rethrow the exception
            throw $ex;
        }
        foreach ($txtarray as $index => $txt) {
            $this->annotatedExpression .= ($index + 1).self::TAB.$txt.self::NL; 
        }
    }

    /**
     * Registers tokens in $this->tokenList up to the first lexer error
     * 
     * @param string $asciiExpression 
     * @return void 
     * @throws isMathException 
     */
    public function getTokens(string $asciiExpression):void {
        $LasciiLexer = new \isLib\LasciiLexer($asciiExpression);
        $LasciiLexer->init();
        $this->tokenList = '';
        $index = 0;
        while ($token = $LasciiLexer->getToken()) {
            $this->tokenList .= $index."\t".$this->blankPad($token['tk'], 10).self::TAB;
            $this->tokenList .= ' --'.$this->blankPad($token['type'], 10).self::TAB;
            $this->tokenList .= 'ln '.$token['ln'].' cl '.$token['cl'].self::TAB;
            $this->tokenList .= 'chPtr '.$token['chPtr'].self::NL;
            $index++;
        }
    }

    public function getSymbols(string $asciiExpression):void {
        $LasciiLexer = new \isLib\LasciiLexer($asciiExpression);
        $LasciiLexer->init();
        $this->symbolList = '';
        $symbolTable = $LasciiLexer->getSymbolTable();
        foreach($symbolTable as $index => $symbol) {
            $this->symbolList .= $index.self::TAB.$symbol['type'].self::NL;
        }
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
}