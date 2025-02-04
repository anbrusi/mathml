<?php

namespace isLib;

class LmathTransformations {
   
    /**
     * the problem containing aas only math expression the expression that should be transformed
     * 
     * @var string
     */
    private string $problem;

    /**
     * The solution containing all the transformations of the problem, that should be checked
     * 
     * @var string
     */
    private string $solution;

    function __construct(string $problem, string $solution) {
        $this->problem = $problem;
        $this->solution = $solution;        
    }

    public function getAnnotatedSolution():string {
        // Get initial formula, which should be transformed
        $Lfilter = new \isLib\Lfilter($this->problem);
        $Lfilter->extractMathContent();
        $problemContent = $Lfilter->getMathContent();
        if (count($problemContent) != 1) {
            \isLib\LmathError::setError(\isLib\LmathError::ORI_MATH_TRANSFORMAUION, 1);
        }
        // Get transformations
        $Lfilter = new \isLib\Lfilter($this->solution);
        $Lfilter->extractMathContent();
        // Compute the problem value
        $problemValue = \isLib\Lfilter::evaluateAsciiFormula($problemContent[0]['ascii'], [], 'deg');
        $solutionContent = $Lfilter->getMathContent();
        if ($solutionContent !== null) {
            // Compute solutions values
            $results = [];
            foreach ($solutionContent as $key => $formula) {
                $results[$key] = \isLib\Lfilter::evaluateAsciiFormula($formula['ascii'], [], 'deg');
            }
            $solution = $this->solution;
            $shift = 0;
            $shiftAmount = strlen('<div class="xxx"></div>');
            // Annotate the first formula
            if ($problemValue = $results[0]) {
                $class = 'gre';
            } else {
                $class = 'red';
            }
            $position = $solutionContent[0]['position'];
            $length = $solutionContent[0]['length'];
            \isLib\Lfilter::annotateFormula($solution, $position, $length, $class);
            $shift += $shiftAmount;
            // Annotate subsequent formulas
            for ($i = 1; $i < count($results); $i++) {
                if ($results[$i] == $results[$i-1]) {
                    $class = 'gre';
                } else {
                    $class = 'red';
                }
                $position = $solutionContent[$i]['position'] + $shift;
                $length = $solutionContent[$i]['length'];
                \isLib\Lfilter::annotateFormula($solution, $position, $length, $class);
                $shift += $shiftAmount;
            }
            return $solution;
        } else {
            \isLib\LmathError::setError(\isLib\LmathError::ORI_FILTER, 1);
        }
    }
}