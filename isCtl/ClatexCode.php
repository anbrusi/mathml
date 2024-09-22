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
            $_POST['currentFile'] = $currentFile;
            $input = \isLib\Ltools::getExpression($currentFile);
            if (\isLib\Ltools::isMathMlExpression($input)) {
                $_POST['errmess'] = 'The current file has a mathML expression';
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $LmathDiag = new \isLib\LmathDiag();
                $parserCheck = $LmathDiag->checkParser($input);
                if (empty($parserCheck['errors'])) {
                    // The parser is ok, proceed with LateX construction
                    $_POST['expression'] = $parserCheck['annotatedExpression'];
                    $_POST['parseTree'] = $parserCheck['parseTree'];
                    $Lparser = new \isLib\LasciiParser($input);
                    $Lparser->init();
                    $parseTree = $Lparser->parse();
                    $latexCheck = $LmathDiag->checkLatex($parseTree);
                    if (empty($latexCheck['errors'])) {
                        $_POST['latex'] = $latexCheck['latex'];
                    } else {
                        $_POST['errors'] = $latexCheck['errors'];
                        $_POST['trace'] = $latexCheck['trace'];
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
        \isLib\LinstanceStore::setView('VshowLatex');
    }
}