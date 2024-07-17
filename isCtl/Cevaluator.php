<?php

namespace isCtl;

class Cevaluator extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'Vevaluator':
                $this->VevaluatorHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VevaluatorHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');
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
            $expression = \isLib\Ltools::getExpression($currentFile);
            $isMathml = \isLib\Ltools::isMathMlExpression($expression);
            if ($isMathml) {
                $expression = \isLib\Ltools::extractMathML($expression)[0];
                $presentationParser = new \isLib\LpresentationParser($expression);
                if ($presentationParser->parse()) {
                    $asciiExpression = $presentationParser->getAsciiOutput();
                } else {
                    $_POST['errors'] =  $presentationParser->showErrors();
                    return;
                }
            } else {
                $asciiExpression = $expression;
            }
            $parser = new \isLib\LasciiParser($asciiExpression);
            $parser->init();
            if ($parser->parse() === false) {
                // A parse error occurred
                $_POST['expression'] = $expression;
                $_POST['variables'] = false;
                $_POST['errors'] = $parser->showErrors();
                return;
            } else {
                $_POST['expression'] = $expression;
                $parseTree = $parser->getParseTree();
                $variables = $parser->getVariableNames(); // If we get here, parsing was successful
                if (!empty($variables)) {
                    // Check if the values have been stored                         
                    $vars = \isLib\Ltools::getVars($currentFile); // Returns the names of the parsed variables or false upon error
                    if ($vars === false)  {
                        // No stored variables available. Build table with unknown values
                        $defaultVars = [];
                        foreach ($variables as $name) {
                            $defaultVars[$name] = '?';
                        }
                        $_POST['variables'] = \isLib\Lhtml::varTable($defaultVars);
                    } else {
                        // There are no variables or variable values have been stored for this expression
                        $_POST['variables'] = \isLib\Lhtml::varTable($vars);
                    }
                } else {
                    $vars = [];                    
                }
                if ($vars !== false) {
                    $evaluator = new \isLib\Levaluator($parseTree, $vars);
                    $evaluation = $evaluator->evaluate();                    
                    $_POST['errors'] = $evaluator->showErrors();
                    if ($_POST['errors'] == '') {
                        if (is_bool($evaluation)) {
                            $_POST['evaluation'] = $evaluation ? 'true' : 'false';
                        } else {
                            $_POST['evaluation'] = strval($evaluation);  
                        }
                    }
                }
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('Vevaluator');
    }
}