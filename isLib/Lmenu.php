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
                $href = \isLib\Ltools::ownRef('?ctl='.$node['ctl']);
                $html .= '<a href="'.$href.'">'.$node['caption'].'</a>';
            } elseif (isset($node['submenu'])) {
                $html .= '<a>'.$node['caption'].'</a>';
                $html .= '<ul>';
                foreach ($node['submenu'] as $subnode) {
                    $html .= '<li>';
                    $href = \isLib\Ltools::ownRef('?ctl='.$subnode['ctl']);
                    $html .= '<a href="'.$href.'">'.$subnode['caption'].'</a>';
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