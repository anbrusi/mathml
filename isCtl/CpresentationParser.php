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
                throw new \Exception('Unimplemented handler for: '.$currentView);
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
                try {
                    $_POST['xmlCode'] = $presentationParser->getXmlCode();
                    $_POST['output'] = $presentationParser->getOutput();
                    $_POST['asciiOutput'] = $presentationParser->getAsciiOutput();
                } catch (\isLib\isMathException $ex) {
                    $_POST['errors'] = 'Parser failed';
                }
            }
        }
    }
    
    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VpresentationParser');
    }
}