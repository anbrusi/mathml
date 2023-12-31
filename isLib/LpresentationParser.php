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

    private string $xmlCode = '';

    private string|false $output = false;

    private string $asciiOutput = '';

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
            $this->output .= $this->indent('&lt;'.$name.'&gt;', $level);
            switch ($name) {
                case 'mrow':
                case 'mfenced':
                case 'mfrac':
                case 'msup':
                    $symbol = '(';
                    $this->asciiOutput .= $symbol;
                    $this->output .= ' ---> '.$symbol;
                    break;
                default:
            }
            $this->output .= "\r\n";
            $this->read();
        } else {
            $this->error('Start of '.$name.' expected');
        }
    }

    private function endNode(string $name, int $level):void {
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == $name) {
            $this->output .= $this->indent('&lt;/'.$name.'&gt;', $level);
            switch ($name) {
                case 'mrow':
                case 'mfenced':
                case 'mfrac':
                case 'msup':
                    $symbol = ')';
                    $this->asciiOutput .= $symbol;
                    $this->output .= ' ---> '.$symbol;
                    break;
                default:
            }
            $this->output .= "\r\n";
            $this->read();
        } else {
            $this->error('End of '.$name.' expected');
        }
    }

    private function groupingNode(string $name, int $level):void {
        $this->startNode($name, $level);
        while (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $this->xmlNode($level + 1);
        }
        $this->endNode($name, $level);
    }

    private function mfracNode(int $level):void {
        $this->startNode('mfrac', $level);
        // Numerator
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $this->xmlNode($level + 1);
        }
        $symbol = ')/(';
        $this->asciiOutput .= $symbol;
        $this->output .= '          ---> '.$symbol;
        $this->output .= "\r\n";
        // Denominator
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $this->xmlNode($level + 1);
        }
        $this->endNode('mfrac', $level);
    }

    private function msupNode(int $level):void {
        $this->startNode('msup', $level);
        // Base
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $this->xmlNode($level + 1);
        }
        $symbol = ')^(';
        $this->asciiOutput .= $symbol;
        $this->output .= '          ---> '.$symbol;
        $this->output .= "\r\n";
        // Exponent
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
            $this->xmlNode($level + 1);
        }
        $this->endNode('msup', $level);
    }

    private function xmlNode(int $level):void {
        $nodeName = $this->xmlReader->name;
        switch ($nodeName) {
            case 'mrow':
            case 'mstyle':
            case 'mfenced':
                $this->groupingNode($nodeName, $level);
                break;
            case 'mfrac':
                $this->mfracNode($level);
                break;
            case 'msup':
                $this->msupNode($level);
                break;
            default:
                $this->startNode($nodeName, $level);
                while (!$this->endOf($nodeName)) {
                    if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
                        $this->xmlNode($level + 1);
                    } elseif (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::TEXT) {
                        $this->output .= $this->indent($this->xmlReader->value, $level + 1);
                        $symbol = $this->xmlReader->value;
                        $this->output .= ' ---> '.$symbol;
                        $this->asciiOutput .= $symbol;
                        $this->output .= "\r\n";
                        $this->read();
                    } else {
                        $this->error('Unexpected input');
                    }
                }
                $this->endNode($nodeName, $level);
        }
    }

    public function parse():bool {
        $this->output = '';
        if (!$this->initParser()) {
            $this->error('Cannot initialize parser');
            return false;
        }
        if (!$this->xmlReader->name == 'math') {
            $this->error('&lt;math&gt; expcted');
            return false;
        }
        $this->xmlNode(0);
        return true;
    }

    public function getOutput():string {
        return $this->output;
    }

    public function getAsciiOutput():string {
        return $this->asciiOutput;
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

    private function startNodeC(string $name, int $level):void {
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == $name) {
            $this->xmlCode .= $this->indent('&lt;'.$name.'&gt;', $level);
            $this->xmlCode .= "\r\n";
            $this->read();
        } else {
            $this->error('Start of '.$name.' expected');
        }
    }

    private function endNodeC(string $name, int $level):void {
        if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == $name) {
            $this->xmlCode .= $this->indent('&lt;/'.$name.'&gt;', $level);
            $this->xmlCode .= "\r\n";
            $this->read();
        } else {
            $this->error('End of '.$name.' expected');
        }
    }

    private function xmlNodeC(string $name, int $level):void {
        $this->startNodeC($name, $level);
        while (!$this->endOf($name)) {
            if (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::ELEMENT) {
                $this->xmlNodeC($this->xmlReader->name, $level + 1);
            } elseif (!$this->endOfInput && $this->xmlReader->nodeType == \XMLReader::TEXT) {
                $this->xmlCode .= $this->indent($this->xmlReader->value, $level + 1);
                $this->xmlCode .= "\r\n";
                $this->read();
            } else {
                $this->error('Unexpected input');
            }
        }
        $this->endNodeC($name, $level);
    }

    public function parseXmlCode():bool {
        $this->xmlCode = '';
        if (!$this->initParser()) {
            $this->error('Cannot initialize parser for code display');
            return false;
        }
        $this->xmlNodeC('math', 0);
        return true;
    }

    public function getXmlCode():string {
        return $this->xmlCode;
    }
}