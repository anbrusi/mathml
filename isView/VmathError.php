<?php

namespace isView;

use IntlBreakIterator;
use isLib\isMathException;

class VmathError extends VviewBase {

    /**
     * The input expression submitted to LmathExpression
     * 
     * @var string
     */
    private string $input;

    /**
     * Set by a POST variable on construction
     * 
     * @var isMathException
     */
    private \isLib\isMathException $ex;

    /** 
     * @param string $name 
     * @return void 
     */
    function __construct(string $name) {
        parent::__construct($name);
        $this->ex = $_POST['ex'];
    }

    private function presentationParserError():string {
        $html = '';
        $xmlInput = $this->ex->info['input'];
        // LpresentationParser must be instantiated independently from the code that threw the exception,
        // since the xml code is generated only on demand by code separate from presentation math parsing.
        try {
            $LpresentationParser = new \isLib\LpresentationParser($xmlInput);
            $xmlCode = $LpresentationParser->getXmlCode();
            $html .= \isLib\Lhtml::fieldset('XML code', $xmlCode);
        } catch (\Exception $ex) {
            $html .= \isLib\Lhtml::fieldset('XML input', htmlentities($xmlInput));
            $html .= \isLib\Lhtml::fieldset('Error in debugging code, trying to decode XML: ', $ex->getMessage());
        }
        $html .= \isLib\Lhtml::fieldset('XML translation', $this->ex->info['translation']);
        return $html;
    }

    private function lexerError():string {
        $html = '';
        $html .= \isLib\Lhtml::fieldset('Annotated source', \isLib\LmathDebug::annotatedExpression($this->ex));
        return $html;
    }

    private function parserError():string {
        $html = '';
        $html .= \isLib\Lhtml::fieldset('Annotated source', \isLib\LmathDebug::annotatedExpression($this->ex));
        $html .= \isLib\Lhtml::fieldset('Lexer tokens', \isLib\LmathDebug::lexerTokens($this->ex));
        $html .= \isLib\Lhtml::fieldset('Traversation', \isLib\LmathDebug::traversation($this->ex));
        return $html;
    }

    private function mathExpressionError() {
        $html = '';
        return $html;
    }

    public function render():string {
        $html = '';
        $html .= '<div class="pagecontent">';
        $html .= \isLib\Lhtml::currentFile();
        $html .= \isLib\Lhtml::fieldset('Error message', $this->ex->getMessage());
        switch (\isLib\LmathDebug::getErrorOrigin($this->ex)) {
            case \isLib\LmathError::ORI_PRESENTATION_PARSER:
                $html .= $this->presentationParserError();
                break;
            case \isLib\LmathError::ORI_LEXER:
                $html .= $this->lexerError();
                break;
            case \isLib\LmathError::ORI_PARSER:
                $html .= $this->parserError();
                break;
            case \isLib\LmathError::ORI_MATH_EXPRESSION:
                $html .= $this->mathExpressionError();
                break;
            default:
                $html .= 'Cannot assess error origin';
        }
        $html .= \isLib\Lhtml::fieldset('Trace', \isLib\LmathDebug::trace($this->ex));
        $html .= '</div>';
        return $html;
    }
}