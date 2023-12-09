<?php

namespace isCtl;

class CpresentationParser extends CcontrollerBase {

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
            case 'VpresentationParser':
                $this->VpresentationParserHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }

    private function VpresentationParserhandler():void {

    }
    
    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VpresentationParser');
    }
}