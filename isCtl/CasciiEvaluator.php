<?php

namespace isCtl;

class CasciiEvaluator extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VasciiEvaluator':
                $this->VasciiEvaluatorHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VasciiEvaluatorHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            $asciiExpression = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($asciiExpression)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                if (isset($_POST['update'])) {
                    if (!\isLib\Ltools::storeVariables($currentFile)) {
                        $_POST['errmess'] = 'No current file set';
                        \isLib\LinstanceStore::setView('Verror');
                        return;
                    }
                }
                $parser = new \isLib\LasciiParser($asciiExpression);
                $parser->init();
                // Just make certain that a parse tree exists
                $parser->parse(); 
                // In cas of error getVars returns false   
                $vars = \isLib\Ltools::getVars($currentFile);
                if ($vars !== false) {
                    $parser->setVariableList($vars);
                    $_POST['variables'] = \isLib\Lhtml::varTable($vars);
                } else {
                    $_POST['variables'] = 'No variables available';
                }
                $_POST['expression'] = $asciiExpression;
                $_POST['evaluation'] = strval($parser->evaluate());     
                $_POST['errors'] = $parser->showErrors();
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VasciiEvaluator');
    }
}