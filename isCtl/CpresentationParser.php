<?php

namespace isCtl;

class CpresentationParser extends CcontrollerBase {

     /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    function __construct(string $name) {
        parent::__construct($name);        
    }
   
    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VpresentationParser':
                $this->VpresentationParserHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: '.$currentView);
        }
    }

    private function VpresentationParserhandler():void {
        if (!\isLib\LinstanceStore::available('currentFile')) {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        } else {
            $currentFile = \isLib\LinstanceStore::get('currentFile');
            $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR . $currentFile, 'r');
            $txt = fgets($ressource);
            $mathmlItems = \isLib\Ltools::extractMathML($txt);
            if (count($mathmlItems) == 0) {
                $_POST['errmess'] = 'No math in current file: '.$currentFile;
                \isLib\LinstanceStore::setView('Verror');
            } else {
                $_POST['source'] = $mathmlItems[0];
                $presentationParser = new \isLib\LpresentationParser($_POST['source']);
                $xmlCode = $presentationParser->showCode();;
                if ($xmlCode === false) {
                    $_POST['xmlCode'] = 'No XML code available';
                } else {
                    $_POST['xmlCode'] = $xmlCode;
                }
                if ($presentationParser->parse()) {
                    $_POST['output'] = $presentationParser->output();
                } else {
                    $_POST['output'] = 'Parser failed';
                }
                // Set this last, in order to reflect previous errors
                $_POST['errors'] = $presentationParser->showErrors();
            }
        }
    }
    
    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VpresentationParser');
    }
}