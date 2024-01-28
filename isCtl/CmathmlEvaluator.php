<?php

namespace isCtl;

class CmathmlEvaluator extends CcontrollerBase {


    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VmathmlEvaluator':
                $this->VmathmlEvaluatorHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VmathmlEvaluatorHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            $expression = \isLib\Ltools::getExpression($currentFile);
            if (!\isLib\Ltools::isMathMlExpression($expression)) {
                $_POST['errmess'] = 'The current file has no mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR . $currentFile, 'r');
                $txt = fgets($ressource);
                $mathmlItems = \isLib\Ltools::extractMathML($txt);
                if (count($mathmlItems) == 0) {
                    $_POST['errmess'] = 'No math in current file: '.$currentFile;
                    \isLib\LinstanceStore::setView('Verror');
                } else {
                    $mathmlExpression = $mathmlItems[0];
                    $parser = new \isLib\LasciiParser('mathml', $mathmlExpression);
                    if ($parser->init() && $parser->parse()) {
                        $_POST['expression'] = $mathmlExpression;
                        $_POST['conversion'] = $parser->showAsciiExpression();
                        $variableNames = $parser->getVariableNames();
                        if ($variableNames === false) {
                            $_POST['variables'] = false;
                            $_POST['evaluation'] = strval($parser->evaluate());  
                            $_POST['errors'] = '';
                        } else {
                            // the expression has variables
                            // check if the values have been stored                         
                            $vars = \isLib\Ltools::getVars($currentFile);
                            if ($vars === false) {
                                // The expression has variables, but they have not yet been stored
                                $defaultVars = [];
                                foreach ($variableNames as $name) {
                                    $defaultVars[$name] = '?';
                                }
                                $_POST['variables'] = \isLib\Lhtml::varTable($defaultVars);
                                $_POST['evaluation'] = strval($parser->evaluate());     
                                $_POST['errors'] = $parser->showErrors();
                            } else {
                                // Set the stored variables and use them for evaluation
                                $parser->setVariableList($vars);
                                $_POST['variables'] = \isLib\Lhtml::varTable($vars);
                                $_POST['evaluation'] = strval($parser->evaluate());     
                                $_POST['errors'] = $parser->showErrors();
                            }
                        }
                    } else {
                        $_POST['expression'] = $mathmlExpression;
                        $_POST['conversion'] = 'No conversion available';
                        $_POST['errors'] = $parser->showErrors();
                    }
                }
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VmathmlEvaluator');
    }
}