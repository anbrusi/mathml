<?php

namespace isLib;

class LpresentationParser {

    /**
     * The presentation mathML, to be parsed
     * 
     * @var string
     */
    private string $mathml;

    /**
     * If $this->errtext !== ' an error preventing continuation has occurred
     * 
     * @var string
     */
    private string $errtext = '';

    private \XMLReader $xmlReader;

    /**
     * True if the last $this->xmlReader returned false
     * 
     * @var bool
     */
    private bool $endOfInput = true;

    private string|false $output = false;

    function __construct(string $mathml) {
        $this->mathml = $mathml;
    }

    private function clearErrors():void {
        $this->errtext = '';
    }

    private function error(string $txt):void {
        $this->errtext .= $txt."\r\n";
        $this->endOfInput = true;
    }

    private function initParser():bool {
        $this->xmlReader = new \XMLReader();
        if (!$this->xmlReader->XML($this->mathml)) {
            return false;
        }
        $this->read();
        return !$this->endOfInput;
    }

    private function read():void {
        $this->endOfInput = !$this->xmlReader->read();
    }

    private function endOf(string $name):bool {
        if ($this->endOfInput) {
            return true;
        }
        return $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == $name;
    }

    private function startNode(string $name, int $level):void {
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == $name) {
            $this->output .= $this->indent('&lt;'.$name.'&gt;'."\r\n", $level);
            $this->read();
        } else {
            $this->error('Start of '.$name.' expected');
        }
    }

    private function endNode(string $name, int $level):void {
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == $name) {
            $this->output .= $this->indent('&lt;/'.$name.'&gt;'."\r\n", $level);
            $this->read();
        } else {
            $this->error('End of '.$name.' expected');
        }
    }

    private function xmlNode(string $name, int $level):void {
        $this->startNode($name, $level);
        while (!$this->endOf($name)) {
            if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
                $this->xmlNode($this->xmlReader->name, $level + 1);
            } elseif (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::TEXT) {
                $this->output .= $this->indent($this->xmlReader->value."\r\n", $level + 1);
                $this->read();
            } else {
                $this->error('Unexpected input');
            }
        }
        $this->endNode($name, $level);
    }

    public function parse():bool {
        $this->output = '';
        if (!$this->initParser()) {
            $this->error('Cannot initialize parser');
            return false;
        }
        $this->xmlNode('math', 0);
        return true;
    }

    public function output():string|false {
        if ($this->output === false) {
            $this->error('No output available');
        }
        return $this->output;
    }

    /*******************************************************
     * The functions below are needed only for testing
     *******************************************************/


    public function showErrors():string {
        return $this->errtext;
    }

    private function indent(string $txt, int $level):string {
        $indent = '';
        while (strlen($indent) < $level) {
            $indent .= ' ';
        }
        return $indent.$txt;
    }

    private function showNode(int $level):string|false {
        $txt = '';
        // Check and digest start element
        if ($this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $txt .= $this->indent($this->xmlReader->name, $level)."\r\n";
            if (!$this->xmlReader->read()) {
                $this->endOfInput = true;
            }
        } else {
            $this->error('ELEMENT expected');
            return false;
        }

        while (!$this->endOfInput && $this->xmlReader->nodeType !== \XMLReader::END_ELEMENT) {
            if ($this->xmlReader->nodeType == \XMLReader::ELEMENT) {
                $txt .= $this->showNode($level + 1);
            } elseif ($this->xmlReader->nodeType == \XMLReader::TEXT) {
                $txt .= $this->indent($this->xmlReader->value, $level + 1)."\r\n";
                if (!$this->xmlReader->read()) {
                    $this->endOfInput = true;
                }
            } else {
                $this->error('unhandled node type '.$this->xmlReader->nodeType);
                return false;
            }
        }

        // Check and digest end element
        if ($this->xmlReader->nodeType == \XMLReader::END_ELEMENT) {
            if (!$this->xmlReader->read()) {
                $this->endOfInput = true;
            }
        } else {
            $this->error('END_ELEMENT expected');
            return false;
        }
        return $txt;
    }

    public function showCode():string|false {
        $txt = '';
        $this->clearErrors();
        $this->xmlReader = new \XMLReader();
        if ($this->xmlReader->XML($this->mathml)) {
            if ($this->xmlReader->read() &&  $this->xmlReader->nodeType == \XMLReader::ELEMENT)  {
                $this->endOfInput = false;
                $txt = $this->showNode(0);
            }
        } else {
            $this->error('Cannot instantiat parser');
        }
        return $txt;
    }
}