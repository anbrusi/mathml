<?php

namespace isCtl;

/**
 * Command syntax
 * 
 *  command     -> assignment | result
 *  assignment  -> storedVar ':=' result
 *  storedVar   -> '$' char { char }
 *  result      -> cmd [variables]
 *  cmd         -> one of the commands in cmdList 
 *  variables   -> '(' variable {',' variable} ')'
 *  variable    -> storedVar | result | literal
 * 
 *  cmdList     -> oneVarCommands | twoVarCommands
 *  oneVarCommands  -> 'strToNn', 'nnToStr'
 * 
 * 
 * @package isCtl
 */
class CncInterpreter extends CcontrollerBase {

    
    const NCT_NATNUMBERS = 1;
    const NCT_INTNUMBERS = 2;
    const NCT_RATNUMBERS = 3;

    private \isLib\LncNaturalNumbers $LncNaturalNumbers;

    function __construct() {
        $this->LncNaturalNumbers = new \isLib\LncNaturalNumbers(\isLib\Lconfig::CF_NC_RADIX);
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

    private function command(string $command):string {
        $command = trim($command);
        if (strpos($command, '$') === 0) {
            return $this->assignment($command);
        } else {
            $result = $this->result($command);
            return $this->displayResult($result, $command);
        }
    }

    private function result(string $command):mixed {
        $cmd = $this->cmd($command);
        $varpart = substr($command,strlen($cmd));
        $variables = $this->variables($varpart);
        switch ($cmd) {
            case 'strToNn':
                return $this->LncNaturalNumbers->strToNn($this->variable($variables[0]));
            case 'nnToStr':
                return $this->LncNaturalNumbers->nnToStr($this->variable($variables[0]));
        }
    }

    private function isLiteral(string $txt):bool {
        return is_numeric($txt);
    }

    private function variable(string $variable):mixed {
        if (is_string($variable)) {
            // Literal or storedVar
            if (strlen($variable) > 0 && $variable[0] == '$') {
                // stored variable
                if (!\isLib\LinstanceStore::NCvarianbleAvailable($variable)) {
                    throw new \Exception('Variable '.$variable.' is not available');
                }
                return \isLib\LinstanceStore::getNCvariable($variable);
            } elseif ($this->isLiteral($variable)) {
                // Literal
                return $variable;
            } else {
                return $this->result($variable);
            }
        } else {
            // result
            return $this->result($variable);
        }
    }

    private function variables(string $varpart):array {
        $varpart = trim($varpart);
        // Get rid of parentheses
        if (strlen($varpart) > 1) {
            $varpart = substr($varpart, 1 , strlen($varpart) - 2);
        }
        $parts = explode(',', $varpart);
        return $parts;
    }

    private function cmd(string $command):string {
        $paren = strpos($command, '(');
        if ($paren === false) {
            return $command;
        } else {
            return substr($command, 0, $paren);
        }
    }

    private function displayResult(mixed $result, string $command):string {
        $cmd = $this->cmd($command);
        switch ($cmd) {
            case 'strToNn':
                return $this->LncNaturalNumbers->showNn($result);
            case 'nnToStr';
                return $result;
            default:
                return 'Unhandled cmd "'.$cmd.'"';
        }
    }

    private function assignment(string $assignment):string {
        $varname = substr($assignment, 0, strpos($assignment, ':='));
        $command = substr($assignment, strpos($assignment, ':=') + 2);
        $result = $this->result($command);
        \isLib\LinstanceStore::setNCvariable($varname, $result);
        return $varname.':='.$this->displayResult($result, $command);
    }

    private function interpretCommand(string $command):string {
        $command = trim($command);
        if (strpos($command, ':=') !== false) {
            return $this->assignment($command);
        } else {
            return $this->command($command);
        }
    }

    public function VncInterpreterHandler():void {
        if (isset($_POST['execCommand']) || isset($_POST['defaultSubmit'])) {
            try {
                if (trim(strtolower($_POST['command'])) == 'clear') {
                    $_POST['result'] = '';
                } elseif (trim(strtolower($_POST['command'])) == 'variables') {
                    $list = \isLib\LinstanceStore::listNCvariables();
                    if (empty($list)) {
                        $_POST['result'] .= 'There are no stored variables'."\n";
                    } else {
                        $out = '';
                        foreach ($list as $key => $value) {
                            $out .= $key.', ';
                        }
                        $_POST['result'] .= '>'.$_POST['command']."\n";
                        $_POST['result'] .= $out."\n";
                    }
                } else {
                    $_POST['result'] .= '>'.$_POST['command']."\n";
                    $response = $this->interpretCommand($_POST['command']);
                    $_POST['result'] .= $response. "\n";
                }
            } catch (\Exception $ex) {
                $_POST['result'] .= $ex->getMessage()."\n";
            }
        }
    }

    public static function setInitialView():void {
        \isLib\LinstanceStore::setView('VncInterpreter');
        $_POST['result'] = '';
    }
}