<?php

namespace isCtl;

use isLib\LasciiParser;

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
            $expression = \isLib\Ltools::getExpression(\isLib\Lconfig::CF_FILES_DIR.$currentFile);
            try {
                if (preg_match('/<math.*?<\/math>/' , $expression, $matches) == 1) {
                    $_POST['originalExpression'] = $expression;
                    $expression = $matches[0];
                    $LpresentationParser = new \isLib\LpresentationParser($expression);
                    // Convert presentation mathml to ASCII
                    $expression = $LpresentationParser->getAsciiOutput();
                }
                $_POST['asciiExpression'] = $expression;
                $LasciiParser = new LasciiParser($_POST['asciiExpression']);
                $LasciiParser->init();
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($LasciiParser->parse());
                $_POST['variableNames'] = $LasciiParser->getVariableNames();
                $_POST['traversation'] = \isLib\LmathDebug::traversationList($LasciiParser->getTraversation());
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
        \isLib\LinstanceStore::setView('VasciiParser');
    }
}