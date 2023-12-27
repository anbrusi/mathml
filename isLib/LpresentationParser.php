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

    function __construct(string $mathml) {
        $this->mathml = $mathml;
    }

    private function clearErrors():void {
        $this->errtext = '';
    }

    private function error(string $txt):void {
        $this->errtext = $txt;
    }

    private function initParser():bool {
        $this->xmlReader = new \XMLReader();
        return $this->xmlReader->XML($this->mathml);
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
        if ($this->initParser()) {
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