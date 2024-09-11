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
            $input = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($input)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $LmathDiag = new \isLib\LmathDiag();
                $_POST['errors'] = '';
                try {
                    $LmathDiag->getExpression($input);
                    $LmathDiag->getTokens($input);
                    $LmathDiag->getSymbols($input);
                } catch (\isLib\isMathException $ex) {
                    $_POST['errors'] = $ex->getMessage();
                }
                $_POST['expression'] = $LmathDiag->annotatedExpression;
                $_POST['tokens'] = $LmathDiag->tokenList;
                $_POST['symbolTable'] = $LmathDiag->symbolList;
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