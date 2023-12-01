<?php

class mathml {


    /**
     * Set by the POST variable 'ctl'' if available. 
     * Default is 'Cavailable' a list of all stored formulas
     * 
     * @var string
     */
    private string $controller = '';

    function __construct() {
        if (isset($_GET['ctl'])) {
            $this->controller = $_GET['ctl'];
        } else {
            $this->controller = 'Cavailable';
        }
    }

    /**
     * Renders the application
     * 
     * @return void 
     */
    public function dispatch():void {
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
        $html .= '<title>MathML</title>';
        return $html;
    }

    /**
     * body of HTML page
     * 
     * @return string 
     */
    private function body():string {
        $html = '';
        $html .= '<body>';
        $html .= '<h1>MathML test environment</h1>';
        $html .= '<form action="index.php" method="POST" enctype="" name="mainform">';
        $html .= $this->mainMenu();
        $className = '\isCtl\\'.$this->controller;
        $controller = new $className();
        $html .= $controller->render();
        $html .= '</form>';
        $html .= '</body>';
        return $html;
    }

    /**
     * Returns a link to page $page. 
     * 
     * @param string $controller 
     * @return string 
     */
    private function ctlAnchor(string $controller) {
        $anchor = '<a class="mainMenu" href="';
		if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] != '')) {
			$prefix = 'https://';
		} else {
			$prefix = 'http://';
		}
        $anchor .= $prefix.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'].'?ctl='.$controller;
        $anchor .= '">';
        return $anchor;
    }

    private function mainMenu():string {
        $html = '';
        $html .= '<p>';
        $html .= '<ul class="mainMenu">';
        $html .= '<li class="mainMenu">'.$this->ctlAnchor('Cavailable').'Available Formulas</a></li>';
        $html .= '<li class="mainMenu">'.$this->ctlAnchor('CnewFormula').'New Formula</a></li>';
        $html .= '<li class="mainMenu">'.$this->ctlAnchor('CshowMathML').'Show MathML</a></li>';
        $html .= '</ul>';
        $html .= '</p>';
        return $html;
    }

}

require 'vendor/autoload.php';

$mathml = new mathml();
$mathml->dispatch();
