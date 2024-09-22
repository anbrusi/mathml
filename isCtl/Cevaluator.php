<?php

namespace isCtl;

use isLib\LmathDiag;

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

    /*
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
                $parseTree = $parser->parse();
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
    */

    private function getVariables(string $currentFile, array $variableNames):array {
        $vars = \isLib\Ltools::getVars($currentFile);
        if ($vars === false) {
            // Build fake values
            $vars = [];
            foreach ($variableNames as $name) {
                $vars[$name] = '?';
            }
        }
        return $vars;
    }

    public function VevaluatorHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            if (isset($_POST['update'])) {
                if (!\isLib\Ltools::storeVariables($currentFile)) {
                    $_POST['errmess'] = 'Cannot update variables';
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
            $_POST['currentFile'] = $currentFile;
            $input = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($input)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
                return;
            } else {
                $_POST['expression'] = $input;
                $LmathDiag = new \isLib\LmathDiag();
                $parserCheck = $LmathDiag->checkParser($input);
                if (empty($parserCheck['errors'])) {
                    // The parser is ok, proceed with the evaluation                  
                    $_POST['expression'] = $parserCheck['annotatedExpression'];
                    $_POST['parseTree'] = $parserCheck['parseTree'];
                    $Lparser = new \isLib\LasciiParser($input);
                    $Lparser->init();
                    $parseTree = $Lparser->parse();
                    $variableNames = $Lparser->getVariableNames();
                    if (empty($variableNames)) {
                        $variables = [];
                    } else {
                        $variables = $this->getVariables($currentFile, $variableNames);
                        $_POST['variables'] = \isLib\Lhtml::varTable($variables);
                    }
                    $evaluationCheck = $LmathDiag->checkEvaluator($parseTree, $variables);
                    if (empty($evaluationCheck['errors'])) {
                        $_POST['evaluation'] = $evaluationCheck['evaluation'];
                    } else {                       
                        $_POST['errors'] = $evaluationCheck['errors'];
                        $_POST['trace'] = $evaluationCheck['trace']; 
                    }
                } else {
                    $_POST['expression'] = $parserCheck['annotatedExpression'];
                    $_POST['errors'] = $parserCheck['errors'];
                    $_POST['trace'] = $parserCheck['trace'];
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