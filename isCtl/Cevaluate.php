<?php

namespace isCtl;

class Cevaluate extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'Vevaluate':
                $this->VevaluateHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VevaluateHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            if (isset($_POST['update'])) {
                if (!\isLib\Ltools::storeVariables($currentFile)) {
                    $_POST['errmess'] = 'Cannot update variables';
                    \isLib\LinstanceStore::setView('Verror');
                }  
            } elseif (isset($_POST['delete'])) {
                if (!\isLib\Ltools::deleteVariables($currentFile)) {
                    $_POST['errmess'] = 'Cannot delete variables of '.$currentFile;
                    \isLib\LinstanceStore::setView('Verror');
                }
            }
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression(\isLib\Lconfig::CF_FILES_DIR.$currentFile);
            try {
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $variableNames = $LmathExpression->getVariableNames(); // Parsed variables
                $missingVarValues = false;
                if (empty($variableNames)) {
                    $vars = [];
                } else {
                    $vars = \isLib\Ltools::getVars($currentFile); // Stored variables name => value
                    if ($vars === false || empty($vars)) {
                        $vars = [];
                        foreach ($variableNames as $variableName) {
                            $vars[$variableName] = '?';
                        }
                        $missingVarValues = true;
                    } 
                    // We set the POST variable only if the expression has variables
                    $_POST['vars'] = $vars;                
                }
                if (!$missingVarValues) {
                    $Levaluator = new \isLib\Levaluator($vars, \isLib\Lconfig::CF_TRIG_UNIT);
                    $parseTree = $LmathExpression->getParseTree();
                    $_POST['evaluation'] = $Levaluator->evaluate($parseTree);
                }
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
        \isLib\LinstanceStore::setView('Vevaluate');
    }
}