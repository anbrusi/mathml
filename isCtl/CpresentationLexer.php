<?php

namespace isCtl;

class CpresentationLexer extends CcontrollerBase
{

    /**
     * 
     * @param string $name The name of the controller
     * @return void 
     */
    function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function viewHandler(): void
    {
        $currentView = \isLib\LinstanceStore::getView();
        switch ($currentView) {
            case 'VpresentationLexer':
                $this->VpresentationLexerHandler();
                break;
            case 'VpresentationLexing':
                $this->VpresentationLexingHandler();
                break;
            default:
                throw new \Exception('Unimplemented hadler for: ' . $currentView);
        }
    }

    private function VpresentationLexerHandler(): void
    {
        if (!\isLib\LinstanceStore::available('currentFile')) {
            $_POST['errmess'] = 'No current file set';
            \isLib\LinstanceStore::setView('Verror');
        }
        if (isset($_POST['lexer'])) {
            if (isset($_POST['available_expressions'])) {
                $currentFile = \isLib\LinstanceStore::get('currentFile');
                $ressource = fopen(\isLib\Lconfig::CF_FILES_DIR . $currentFile, 'r');
                $txt = fgets($ressource);
                $items = \isLib\Ltools::extractMathML($txt);
                $_POST['source'] = $items[$_POST['available_expressions']];
                $presentationLexer = new \isLib\LpresentationLexer($_POST['source']);
                $_POST['xmlCode'] = $presentationLexer->showXmlCode();
                $_POST['tokens'] = $presentationLexer->showTokens();
                $_POST['errors'] = $presentationLexer->showErrors();
                \isLib\LinstanceStore::setView('VpresentationLexing');
            } else {
                $_POST['errmess'] = 'No expression chosen or none availabe';
                \isLib\LinstanceStore::setView('Verror');
            }
        }
    }

    private function VpresentationLexingHandler(): void
    {
    }

    public static function setInitialView(): void
    {
        \isLib\LinstanceStore::setView('VpresentationLexer');
    }
}
