<?php

namespace isLib;

use Exception;

class Lnavigation {

    /**
     * An abstraction of a menu structure.
     * An array of main menu entries. 
     * Each main menu entry is an array with keys 'caption' and 
     * 'ctl' for the handling controller if it is a final main menu entry or 'submenu' if it is only parent of a submenu.
     * The value of 'submenu' is again an array of menu entries. 
     */
    public const mainMenu = [
        [ 'caption' => 'Formula', 'ctl' => 'Cformula' ],
        [ 'caption' => 'Lexer', 
          'submenu' => [
                [ 'caption' => 'ASCIImath Lexer', 'ctl' => 'CasciimathLexer' ],
                [ 'caption' => 'Presentation MathML Lexer', 'ctl' => 'CpresentationLexer' ]
            ] 
        ],
        [ 'caption' => 'Parser', 
          'submenu' => [
                [ 'caption' => 'ASCIImath Parser', 'ctl' => 'CasciimathParser' ],
                [ 'caption' => 'Presentation MathML Parser', 'ctl' => 'CpresentationParser' ]

            ]
        ]
    ];

    /**
     * Renders HTML for a menu structure, as described in self::mainMenu
     * Final menu points submit the name of the handling controller as $_POST['ctl']
     * 
     * @param string $cssClass 
     * @param array $menu the abstract menu structure
     * @return string 
     * @throws Exception 
     */
    public static function dropdownBar(string $cssClass, array $menu):string {
        $html = '';
        $html .= '<nav class="'.$cssClass.'">';
        $html .= '<ul>';
        foreach ($menu as $node) {
            $html .= '<li>';
            if (isset($node['ctl'])) {
                // No submenu
                $html .= '<button type="submit" name="ctl", value="'.$node['ctl'].'">'.$node['caption'].'</button>';
            } elseif (isset($node['submenu'])) {
                $html .= '<div>'.$node['caption'].'</div>';
                $html .= '<ul>';
                foreach ($node['submenu'] as $subnode) {
                    $html .= '<li>';
                    $html .= '<button type="submit" name="ctl", value="'.$subnode['ctl'].'">'.$subnode['caption'].'</button>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            } else {
                throw new \Exception('Invalid menu');
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</nav>';
        return $html;
    }
}