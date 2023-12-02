<?php

namespace isLib;

class Lmenu {

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