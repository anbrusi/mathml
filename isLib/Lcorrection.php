<?php

namespace isLib;

class Lcorrection {

    public function getTeacherSolution(string $html):void {
        $LmathExpression = new \isLib\LmathExpression($html);
        
    }
}