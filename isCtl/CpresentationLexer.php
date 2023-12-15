<?php

namespace isCtl;

class CpresentationLexer extends CcontrollerBase {

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
            case 'VpresentationLexer':
                $this->VpresentationLexerHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }
    
    private function VpresentationLexerHandler():void {
        if (!\isLib\LinstanceStore::available('currentFile')) {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }
    
    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VpresentationLexer');
    }
}