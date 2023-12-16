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
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }
    
    private function getExpression():string {
        $currentFile = \isLib\LinstanceStore::get('currentFile');
        $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$currentFile, 'r');
        $expression = fgets($ressource);
        $expression = str_replace('<p>', '', $expression);
        $expression = str_replace('</p>', "\r\n", $expression);
        $expression = html_entity_decode($expression);
        return $expression;
    }

    private function VasciiLexerHandler():void {       
        if (\isLib\LinstanceStore::available('currentFile')) {            
            $_POST['expression'] = $this->getExpression();
            $lexer = new \isLib\LasciiLexer($_POST['expression']);
            $lexer->init();
            $_POST['tokens'] = $lexer->showTokens();
            $_POST['errors'] = $lexer->showErrors();
            $_POST['symbolTable'] = $lexer->showSymbolTable();
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiLexer');
    }
}