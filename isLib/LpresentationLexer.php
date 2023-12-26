<?php
namespace isLib;

use XMLReader;

class LpresentationLexer {

    /**
     * The presentation mathML expression to analyze
     * 
     * @var string
     */
    private string $mathml;

    /**
     * The reader used to read $this->mathml
     * 
     * @var XMLReader
     */
    private \XMLReader $xmlReader;

    /**
     * true iff $this->xmlreader is a valid XML node obtained by advancing the cursor
     * 
     * @var bool
     */
    private bool $valid = false;

    /**
     * Text describing the last error
     * 
     * @var string
     */
    private string $errtext = '';

    function __construct(string $mathml) {
        $this->mathml = $mathml;
        $this->xmlReader = new \XMLReader();
        if (!$this->xmlReader->XML($this->mathml)) {
            $this->error('Cannot initialize xml reader');
        }
        $this->advanceCursor();
        if (!$this->valid ) {
            $this->error('Expression empty');
        }
    }

    private function error(string $txt):void {
        if ($this->errtext == '') {
            $this->errtext = $txt;
        }
    } 

    private function advanceCursor():void {
        $this->valid = $this->xmlReader->read();
    }

    private function handledElement():bool {
        return ($this->xmlReader->nodeType == \XMLReader::ELEMENT && in_array($this->xmlReader->name, ['mn', 'mi', 'mo']));
    }

    private function handledNode():bool {
        return $this->handledElement();
    }

    public function getToken():array|false {
        if ($this->errtext != '') {
            return false;
        }
        while ($this->valid && !$this->handledNode()) {
            $this->advanceCursor();
        }
        if (!$this->valid) {
            return false;
        }
        switch ($this->xmlReader->nodeType) {
            case \XMLReader::ELEMENT:
                return $this->readElement();
            default:
                $token = [ 'tk' => 'unknown'];
                $this->advanceCursor();
                return $token;
        }
    }

    /*
    private function readInteger():array|false {
        $this->advanceCursor();
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::TEXT) {
            $number = $this->xmlReader->value;
            $this->advanceCursor();
        } else {
            $this->error('Number expected');
            return false;
        }
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == 'mn') {
            $this->advanceCursor();
            $s = $this->xmlReader->readInnerXml();
            return [ 'tk' => $number];
        } else {
            $this->error('END_ELEMENT mn expected');
            return false;
        }
    }
    */

    private function readInteger():string|false {
        if (!($this->valid && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == 'mn')) {
            $this->error('mn expected');
            return false;
        }
        $this->advanceCursor();
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::TEXT) {
            $integer = $this->xmlReader->value;
            $this->advanceCursor();
        } else {
            $this->error('Integer expected');
            return false;
        }
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == 'mn') {
            $this->advanceCursor();
            return $integer;
        } else {
            $this->error('END_ELEMENT mn expected');
            return false;
        }
    }

    private function readNumber():array|false {
        $number = $this->readInteger();
        if ($number === false) {
            $this->error('Integer expected');
            return false;
        }
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == 'mo') {
            if ($this->xmlReader->readInnerXml() == '.') {
                $this->advanceCursor();
                $this->advanceCursor();
                $this->advanceCursor();
                $decimalPart = $this->readInteger();
                if ($decimalPart === false) {
                    $this->error('Decimal part of number expected');
                    return false;
                }
                $number .= '.'.$decimalPart;
            }
        }
        return ['tk' => $number];
    }

    private function readAlpha():string|false {
        if (!($this->valid && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == 'mi')) {
            $this->error('mi expected');
            return false;
        }
        $this->advanceCursor();
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::TEXT) {
            $alpha = $this->xmlReader->value;
            $this->advanceCursor();
        } else {
            $this->error('Alpha expected');
            return false;
        }
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == 'mi') {
            $this->advanceCursor();
            return $alpha;
        } else {
            $this->error('END_ELEMENT mi expected');
            return false;
        }

    }

    private function readId():array|false {
        $id = $this->readAlpha();
        if ($id === false) {
            $this->error('Alpha expected');
            return false;
        }
        while ($this->valid && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == 'mi') {
            $nextAlpha = $this->readAlpha();
            if ($nextAlpha === false) {
                $this->valid = false;
                $this->error('Alpha expected');
                return false;
            }
            $id .= $nextAlpha;
        }
        if ($this->valid && $this->xmlReader->nodeType == \XMLReader::ELEMENT && $this->xmlReader->name == 'mn') {
            $integer = $this->readInteger();
            if ($integer === false) {
                $this->error('Integer expected');
                return false;
            }
            $id .= $integer;
        }
        return ['tk' => $id];
    }

    private function readElement():array|false {
        $name = $this->xmlReader->name;
        if ($name == 'mn') {
            return $this->readNumber();
        } elseif ($name == 'mi') {
            return $this->readId();
        } elseif ($name == 'mo') {
            $this->advanceCursor();
            if ($this->valid && $this->xmlReader->nodeType == \XMLReader::TEXT) {
                $operator = $this->xmlReader->value;
                $this->advanceCursor();
            } else {
                $this->error('Operator expected');
                return false;
            }
            if ($this->valid && $this->xmlReader->nodeType == \XMLReader::END_ELEMENT && $this->xmlReader->name == 'mo') {
                $this->advanceCursor();
                return [ 'tk' => $operator];
            } else {
                $this->error('END_ELEMENT mi expected');
                return false;
            }
        }
        $token = [ 'tk' => $this->xmlReader->name];
        $this->advanceCursor();
        return $token;
    }

    /*******************************************************
     * The functions below are needed only for testing
     *******************************************************/

     private function nodeType(int $type):string {
        switch ($type) {
            case 1:
                return 'ELEMENT';
            case 3:
                return 'TEXT';
            case 15:
                return 'END_ELEMENT';
            default:
                return 'unknown '.$type;
        }
     }

     private function blankPad(string $txt, int $length):string {
        while (strlen($txt) < $length) {
            $txt .= ' ';
        }
        return $txt;
     }

     private function indent(string $txt, int $length):string {
        $indent = '';
        while (strlen($indent) < $length) {
            $indent .= ' ';
        }
        return $indent.$txt;
     }

     private function readNode(\XMLReader $xmlReader, int $level):string|false {
        // Precondition
        if (!$this->xmlReader->nodeType === \XMLReader::ELEMENT) {
            $this->error('ELEMENT expected');
            return false;
        }

        $txt = '';
        $txt .= $this->indent($xmlReader->name, $level)."\r\n";
        $xmlReader->read();
        while ($xmlReader->nodeType !== \XMLReader::END_ELEMENT) {
            if ($xmlReader->nodeType == \XMLReader::ELEMENT) {
                $txt .= $this->readNode($xmlReader, $level + 1);
            } elseif ($xmlReader->nodeType == \XMLReader::TEXT) {
                $txt .= $this->indent($xmlReader->value, $level + 1)."\r\n";
                $xmlReader->read();
            }
        }

        // Postcondition
        if (!$this->xmlReader->nodeType === \XMLReader::END_ELEMENT) {
            $this->error('END_ELEMENT expected');
            return false;
        }
        // Digest end element
        // $txt .= $this->indent('/'.$xmlReader->name, $level)."\r\n"; 
        $xmlReader->read();
        return $txt;
     }

     public function showXmlCode(): string {
        /*
        $xmlReader = new XMLReader();
        $txt = '';
        if ($xmlReader->XML($this->mathml)) {
            while ($xmlReader->read()) {
                $txt .= $this->blankPad($this->nodeType($xmlReader->nodeType), 16).$xmlReader->name."\t".$xmlReader->value."\r\n";
            }
        } else {
            $txt .= 'Failed to load XML';
        }
        return $txt;
        */


        $xmlReader = new XMLReader();
        if ($xmlReader->XML($this->mathml) && $xmlReader->read()) {
            $txt = $this->readNode($xmlReader, 0);
        }
        if ($txt === false) {
            $txt = 'Error in schowXmlCode';
        }
        return $txt;
    }

    public function showTokens():string {
        $tokens = [];
        while ($token = $this->getToken()) {
            $tokens[] = $token;
        }
        $txt = '';
        foreach ($tokens as $token) {
            $txt .= $token['tk']."\r\n";
        }
        return $txt;
    }

    public function showErrors():string {
        return $this->errtext;
    }
}