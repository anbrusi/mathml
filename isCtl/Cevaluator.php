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

    private function getVariables(string $currentFile, array $variableNames):array {
        $vars = \isLib\Ltools::getVars($currentFile);
        if ($vars === false) {
            // No stored variables found. Build fake values
            $vars = [];
            foreach ($variableNames as $name) {
                $vars[$name] = '?';
            }
        } else {
            // Update variables
            $updatedVars = [];
            foreach ($variableNames as $name) {
                if (isset($vars[$name])) {
                    $updatedVars[$name] = $vars[$name];
                } else {
                    $updatedVars[$name] = '?';
                }
            }
            $vars = $updatedVars;           
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
            }
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
                $evaluationCheck = $LmathDiag->checkEvaluator($parseTree, $variables, \isLib\Lconfig::CF_TRIG_UNIT);
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
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('Vevaluator');
    }
}