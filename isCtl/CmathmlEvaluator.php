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
            $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR.$currentFile, 'r');
            $mathml = fgets($ressource);
            if (!\isLib\Ltools::isMathMlExpression($mathml)) {
                $_POST['errmess'] = 'The current file is not mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $mathmlExpression = \isLib\Ltools::extractMathML($mathml)[0];
                $parser = new \isLib\LasciiParser('mathml', $mathmlExpression);
                if (!$parser->init()) {                    
                    $_POST['expression'] = $mathmlExpression;
                    $_POST['conversion'] = false;
                    $_POST['variables'] = false;
                    $_POST['errors'] = $parser->showErrors();
                    return;
                }
                // Parse first
                if ($parser->parse() === false) {
                    // A parse error occurred
                    $_POST['expression'] = $mathmlExpression;
                    $_POST['conversion'] = false;
                    $_POST['variables'] = false;
                    $_POST['errors'] = $parser->showErrors();
                    return;
                }
                // Handle expression without variables
                $variableNames = $parser->getVariableNames();
                if ($variableNames === false) {
                    $_POST['expression'] = $mathmlExpression;
                    $_POST['conversion'] = $parser->showAsciiExpression();
                    $_POST['variables'] = false;
                    $_POST['errors'] = $parser->showErrors();
                    return;
                }

                if (isset($_POST['update'])) {
                    if (!\isLib\Ltools::storeVariables($currentFile)) {
                        $_POST['errmess'] = 'No current file set';
                        \isLib\LinstanceStore::setView('Verror');
                        return;
                    }    
                }
                if (isset($_POST['delete'])) {
                    if (!\isLib\Ltools::deleteVariables($currentFile)) {
                        $_POST['errmess'] = 'Cannot deelete variables of '.$currentFile;
                        \isLib\LinstanceStore::setView('Verror');
                        return;
                    }
                }
                if (empty($variableNames)) {
                    // there are no variables
                    $_POST['expression'] = $mathmlExpression;
                    $_POST['conversion'] = $parser->showAsciiExpression();
                    $_POST['variables'] = false;
                    $evaluation = $parser->evaluate();
                    if (is_bool($evaluation)) {
                        $_POST['evaluation'] = $evaluation ? 'true' : 'false';
                    } else {
                        $_POST['evaluation'] = strval($evaluation);  
                    }
                    $_POST['errors'] = $parser->showErrors();
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
                        $_POST['expression'] = $mathmlExpression; 
                        $_POST['conversion'] = $parser->showAsciiExpression();
                        $_POST['variables'] = \isLib\Lhtml::varTable($defaultVars);
                        $evaluation = $parser->evaluate();
                        if (is_bool($evaluation)) {
                            $_POST['evaluation'] = $evaluation ? 'true' : 'false';
                        } else {
                            $_POST['evaluation'] = strval($evaluation);  
                        }
                        $_POST['errors'] = $parser->showErrors();
                    } else {
                        // Set the stored variables and use them for evaluation
                        $_POST['expression'] = $mathmlExpression;
                        $parser->setVariableList($vars);
                        $_POST['variables'] = \isLib\Lhtml::varTable($vars);
                        $_POST['conversion'] = $parser->showAsciiExpression();
                        $evaluation = $parser->evaluate();
                        if (is_bool($evaluation)) {
                            $_POST['evaluation'] = $evaluation ? 'true' : 'false';
                        } else {
                            $_POST['evaluation'] = strval($evaluation);  
                        }
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