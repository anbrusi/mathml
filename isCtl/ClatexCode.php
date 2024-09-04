<?php

namespace isCtl;

class ClatexCode extends Ccontrollerbase {

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VshowLatex':
                $this->VshowLatexHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    public function VshowLatexHandler():void {
        if (\isLib\LinstanceStore::available('currentFile')) {  
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            $mathExpression = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($mathExpression)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $parser = new \isLib\LasciiParser($mathExpression);
                $parser->init();
                if ($parser->parse()) {    
                    $_POST['parseTree'] = $parser->showParseTree();
                    $Llatex = new \isLib\Llatex($parser->getParseTree());
                    if ($Llatex->makeLateX()) {
                        $_POST['latex'] = $Llatex->getLateX();
                    } else {
                        $_POST['errors'] = $Llatex->showErrors();
                    }
                } else {
                    $_POST['errmess'] = 'The ascii expresion could not be successfully parsed';
                    \isLib\LinstanceStore::setView('Verror');
                }
            }
        } else {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VshowLatex');
    }
}