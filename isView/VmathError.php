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