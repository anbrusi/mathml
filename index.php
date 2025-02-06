<?php

class mathml {

    function __construct() {
        if (!\isLib\LinstanceStore::init()) {
            throw new \Exception('Could not initialize LinstanceStore');
        }
        if (!\isLib\LinstanceStore::controllerAvailable()) {
            // Set the initial controller
            \isLib\LinstanceStore::setController('Cformula');
        }
        if (!\isLib\LinstanceStore::viewAvailable()) {
            // Set the initial view
            \isLib\LinstanceStore::setView('VadminFormulas');
        }
    }

    /**
     * Renders the application
     * 
     * @return void 
     */
    public function dispatch():void {
        if (isset($_POST['ctl'])) {
            \isLib\LinstanceStore::setController($_POST['ctl']);
            $className = '\isCtl\\'.$_POST['ctl'];
            $className::setInitialView();
        }
        echo $this->renderPage();
    }

    /**
     * Returns the application HTML page
     * 
     * @return string 
     */
    private function renderPage():string {
        $html = '';
        $html .= '<!DOCTYPE html>';
        $html .= '<html lang="en">';
        $html .= $this->header();
        $html .= $this->body();
        $html .= '</html>';
        return $html;
    }

    /**
     * Header of HTML page
     * 
     * @return string 
     */
    private function header():string {
        $html = '';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<link rel="stylesheet" href="index.css" />';
        // From CKEditor Version 42 on styles for both the editor and produced content are set by a stylesheet
        $html .= '<link rel="stylesheet" href="ckeditor_5_2/style.css" />';
        // Import the classic editor script for all pages. Instantiation is made in pages, that need it
        $html .= '<script src="./ckeditor_5_2/isCkeditor.umd.cjs"></script>'; 
        // Wiris client rendering
        $html .= '<script src="'.\isLib\Lconfig::urlBase().'ckeditor_5_2/wiris/integration/WIRISplugins.js?viewer=image"></script>';
        // MathJax
        $html .= '<script type="text/javascript" id="MathJax-script" async
                    src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js">
                </script>';
        $html .= '<title>MathML</title>';
        return $html;
    }

    /**
     * body of HTML page
     * 
     * @return string 
     */
    private function body():string {
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        $html = '';
        $html .= '<body>';
        $html .= '<h1>MathML test environment</h1>';
        $html .= '<form action="index.php" method="POST" enctype="" name="mainform" id="mainform">';
        // This is to have a first submit to which one can react, when 'Enter' is pressed. Otherwise the first menu option is executed on 'Enter'
        $html .= '<input type="submit" name="defaultSubmit" value="H" class="hidden" />';
        $html .= \isLib\LinstanceStore::propagation();
        $html .= \isLib\Lnavigation::dropdownBar('navbar', \isLib\Lnavigation::mainMenu);
        $controller = \isLib\LinstanceStore::getController();
        $className = '\isCtl\\'.$controller;
        $controllerObj = new $className($controller);
        $controllerObj->viewHandler();
        $html .= '<div class="vchint">'.\isLib\LinstanceStore::getController().'/'.\isLib\LinstanceStore::getView().'</div>';
        $html .= $controllerObj->render();
        $html .= '</form>';
        $html .= '</body>';
        return $html;
    }
}

require 'vendor/autoload.php';

$mathml = new mathml();
$mathml->dispatch();
