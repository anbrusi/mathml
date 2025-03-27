<?php

namespace isCtl;

class CdistLaw extends CcontrollerBase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VdistLaw':
                $this->VdistLawHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VdistLawHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile'); 
            $_POST['currentFile'] = $currentFile;
            $_POST['input'] = \isLib\Ltools::getExpression($currentFile);
            try {
                // Original expression
                $LmathExpression = new \isLib\LmathExpression($_POST['input']);
                $originalTree = $LmathExpression->getParseTree();
                $_POST['originalTree'] = \isLib\LmathDebug::drawParseTree($originalTree);
                // Transformed expression
                $LtreeTrf = new \isLib\LtreeTrf($originalTree);
                $trfTree = $LtreeTrf->applyDistLaw();
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($trfTree);
                // LateX
                $Llatex = new \isLib\Llatex($trfTree);
                $_POST['latex'] = $Llatex->getLatex();
                // Debug function
                $_POST['trfSequence'] = $LtreeTrf->getTrfSequence();
                // Evaluation. If there are variables, we get their values
                $variableNames = $LmathExpression->getVariableNames(); // Parsed variables
                if (empty($variableNames)) {
                    $vars = [];
                } else {
                    $vars = \isLib\Ltools::getVars($currentFile); // Stored variables name => value
                    if ($vars == false || empty($vars)) {
                        // Missing variable values
                        \isLib\LmathError::setError(\isLib\LmathError::ORI_TREE_TRANSFORMS, 9);
                    }
                }
                $Levaluator = new \isLib\Levaluator($originalTree, $vars, 'deg');
                $_POST['originalValue'] = $Levaluator->evaluate();
                $Levaluator = new \isLib\Levaluator($trfTree, $vars, 'deg');
                $_POST['trfValue'] = $Levaluator->evaluate();
            } catch (\isLib\isMathException $ex) {
                $_POST['ex'] = $ex;
                \isLib\LinstanceStore::setView('VmathError');
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView(): void {        
        \isLib\LinstanceStore::setView('VdistLaw');
    }
}