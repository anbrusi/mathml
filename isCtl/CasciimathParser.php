<?php

namespace isCtl;

class CasciimathParser extends CcontrollerBase {

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
            case 'VasciiParser':
                $this->VasciiParserHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }
    
    private function VasciiParserHandler():void {    
        if (\isLib\LinstanceStore::available('currentFile')) {            
            $_POST['expression'] = \isLib\Ltools::getExpression();
            $parser = new \isLib\LasciiParser($_POST['expression']);
            $parser->init();
            $_POST['tokens'] = $parser->showTokens();
            $_POST['errors'] = $parser->showErrors();
            $_POST['parseTree'] = $parser->showParseTree();
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiParser');
    }
}