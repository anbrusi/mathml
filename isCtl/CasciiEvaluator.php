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
                $parser = new \isLib\LasciiParser('ascii', $asciiExpression);
                $parser->init();
                // Parse first
                if ($parser->parse() === false) {
                    // A parse error occurred
                    $_POST['expression'] = $asciiExpression;
                    $_POST['variables'] = false;
                    $_POST['errors'] = $parser->showErrors();
                    return;
                }
                // Handle expression without variables
                $variableNames = $parser->getVariableNames();
                if ($variableNames === false) {
                    $_POST['expression'] = $asciiExpression;
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
                    $_POST['expression'] = $asciiExpression;
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
                        $_POST['expression'] = $asciiExpression; 
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
                        $_POST['expression'] = $asciiExpression;
                        $parser->setVariableList($vars);
                        $_POST['variables'] = \isLib\Lhtml::varTable($vars);
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
        \isLib\LinstanceStore::setView('VasciiEvaluator');
    }
}