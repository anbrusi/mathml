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

    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiParser');
    }
}