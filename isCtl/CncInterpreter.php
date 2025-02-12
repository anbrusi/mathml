<?php

namespace isCtl;

/**
 * Command syntax
 * 
 *  command     -> assignment | result
 *  assignment  -> storedVar ':=' result
 *  storedVar   -> '$' char {char}
 *  result      -> LncInterpreter result
 * 
 * 
 * @package isCtl
 */
class CncInterpreter extends CcontrollerBase {    
   
    private \isLib\LncInterpreter $LncInterpreter;
    private \isLib\LncNaturalNumbers $LncNaturalNumbers;
    private \isLib\LncIntegers $LncIntegers;
    private \isLib\LncVarStore $LncVarStore;

    function __construct() {
        $this->LncInterpreter = new \isLib\LncInterpreter();
        $this->LncNaturalNumbers = new \isLib\LncNaturalNumbers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncIntegers = new \isLib\LncIntegers(\isLib\Lconfig::CF_NC_RADIX);
        $this->LncVarStore = new \isLib\LncVarStore();
    }

    public function viewHandler():void {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VncInterpreter':
                $this->VncInterpreterHandler();
                break;
            default:
                throw new \Exception('Unimplemented handler for: '.$currentView);
        }
    }

    /**
     * Returns the string $txt, without any spaces
     * This is faster and simpler, than repeated trimming
     * 
     * @param string $txt 
     * @return string 
     */
    private function noSpace(string $txt):string {
        return str_replace(' ', '', $txt);
    }

    private function displayResult(mixed $result):string {
        switch ($result['type']) {
            case \isLib\LncInterpreter::NCT_NATNUMBERS:
                return $this->LncNaturalNumbers->showNn($result['value'])."\n";
            case \isLib\LncInterpreter::NCT_INTNUMBERS:
                return $this->LncIntegers->showInt($result['value'])."\n";
            default:
                throw new \Exception('Unhandlrd nanoCAS type in CncInterpreter->displayResult');
        }
    }

    private function assignment(string $assignment):string {
        $varname = substr($assignment, 0, strpos($assignment, ':='));
        $command = substr($assignment, strpos($assignment, ':=') + 2);
        $result = $this->LncInterpreter->cmdToNcObj($command);
        $this->LncVarStore->storeVar($varname, $result);
        return $varname.':='.$this->displayResult($result);
    }

    private function interpretCommand(string $command):string {
        $command = $this->noSpace($command);
        if (strpos($command, ':=') !== false) {
            return $this->assignment($command);
        } else {
            $result = $this->LncInterpreter->cmdToNcObj($command);
            return $this->displayResult($result);
        }
    }

    public function VncInterpreterHandler():void {
        if (isset($_POST['execCommand']) || isset($_POST['defaultSubmit'])) {
            try {
                if (strtolower($_POST['command']) == 'clear') {
                    $_POST['result'] = '';
                } elseif (strtolower($_POST['command']) == 'variables') {
                    $list = $this->LncVarStore->listVariables();
                    if (empty($list)) {
                        $_POST['result'] .= 'There are no stored variables'."\n";
                    } else {
                        $out = '';
                        foreach ($list as $key => $value) {
                            $out .= $key.' = '.$this->displayResult($value);
                        }
                        $_POST['result'] .= '>'.$_POST['command']."\n";
                        $_POST['result'] .= $out."\n";
                    }
                } else {                    
                    $_POST['result'] .= '>'.$_POST['command']."\n";
                    $_POST['result'] .= $this->interpretCommand($_POST['command']);                     
                }
            } catch (\isLib\isMathException $ex) {
                $errtxt = $ex->info['errtxt'];
                $_POST['result'] .= $ex->getMessage().':  '.$errtxt."\n";
            } catch(\Exception $ex) {
                $_POST['result'] .= $ex->getMessage()."\n";
            }
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VncInterpreter');
        $_POST['result'] = '';
    }
}