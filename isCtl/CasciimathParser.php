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
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $input = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($input)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $LmathDiag = new \isLib\LmathDiag();
                $check = $LmathDiag->checkParser($input);                    
                $_POST['expression'] = $check['annotatedExpression'];
                $_POST['errors'] = $check['errors'];
                $_POST['trace'] = $check['trace'];
                $_POST['parseTree'] = $check['parseTree'];
                $_POST['variables'] = $check['variables'];
                $_POST['traversation'] = $check['traversation'];
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiParser');
    }
}