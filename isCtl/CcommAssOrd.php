<?php

namespace isCtl;

class CcommAssOrd extends Ccontrollerbase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VcommAssOrd':
                $this->VcommAssOrdHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    private function showSummands(array $summands):string {
        $txt = '';
        foreach ($summands as $summand) {
            $desc = $summand[1];
            $tk = $summand[0]['tk'];
            $txt .= $desc."\t".$tk."\n";
        }
        return $txt;
    }

    public function VcommAssOrdHandler():void {
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
                $trfTree = $LtreeTrf->commAssOrd($originalTree);
                $summands = $LtreeTrf->getSummands();
                $_POST['summands'] = $this->showSummands($summands);
                $_POST['parseTree'] = \isLib\LmathDebug::drawParseTree($trfTree);
                // LateX
                $Llatex = new \isLib\Llatex($trfTree);
                $_POST['latex'] = $Llatex->getLatex();
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
        \isLib\LinstanceStore::setView('VcommAssOrd');
    }
}