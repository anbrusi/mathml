<?php

namespace isCtl;

class CasciimathLexer extends CcontrollerBase {

    /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    function __construct(string $name) {
        parent::__construct($name);        
    }
    
    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VasciiLexer':
                $this->VasciiLexerHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }
    
    private function VasciiLexerHandler():void {       
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');   
            $_POST['currentFile'] = $currentFile;       
            $expression = \isLib\Ltools::getExpression(\isLib\Lconfig::CF_FILES_DIR.$currentFile);
            try {
                if (preg_match('/<math.*?<\/math>/' , $expression, $matches) == 1) {
                    $_POST['mathml'] = $expression;
                    $expression = $matches[0];
                    $LpresentationParser = new \isLib\LpresentationParser();
                    // Convert presentation mathml to ASCII
                    $expression = $LpresentationParser->getAsciiOutput($expression);
                }
                $Llexer = new \isLib\LasciiLexer($expression);
                $Llexer->init();
                $_POST['expression'] = $expression;
                $_POST['tokens'] = \isLib\LmathDebug::tokenList($expression);
                $_POST['symbolTable'] = \isLib\LmathDebug::drawSymbolTable($Llexer->getSymbolTable());
            } catch (\isLib\isMathException $ex) {
                $_POST['ex'] = $ex;
                \isLib\LinstanceStore::setView('VmathError');
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiLexer');
    }
}