<?php

namespace isLib;

class Lgauss {

    private const ZERO_BARRIER = 1E-9;

    /**
     * Returns the index of the line with the absolute largest pivot candidate
     * The search in the matrix $a is made in column $col, starting with line $step and ending at the last line of $a
     *   
     * @param array &$a 
     * @param int $step 
     * @param int $col 
     * @return int 
     */
    private function getPivot(array &$a, int $step, int $col):int {
        $nrLines = count($a); 
        $result = $step;
        for ($i = $step + 1; $i < $nrLines; $i++) {
            if (abs($a[$i][$col]) > abs($a[$result][$col])) {
                $result = $i;
            }
        }
        return $result;
    }

    /**
     * Exchanges lines $step and $best in the matrix $a
     * 
     * @param array &$a 
     * @param int $step 
     * @param int $best
     * @return void 
     */
    private function switchLines(array &$a, int $step, int $best):void {
        $s = $a[$best];
        $a[$best] = $a[$step];
        $a[$step] = $s;
    }

    private function isZero(float $p):bool {
        return abs($p) < self::ZERO_BARRIER;
    }

    private function eliminationStep(array &$a, int $step):bool {
        $nrLines = count($a);
        $nrColumns = count($a[1]);
        if ($step > $nrColumns || $step > $nrLines - 2) {
            return false; // Terminated no further step needed
        }
        $pivotLine = $this->getPivot($a, $step, $step);
        $pivot = $a[$pivotLine][$step];
        if ($this->isZero($pivot)) {
            return true;
        }
        if ($pivotLine != $step) {
            $this->switchLines($a, $step, $pivotLine);
        }
        for ($i = $step + 1; $i < $nrLines; $i++) {
            $f = $a[$i][$step]/$pivot;
            for ($j = $step; $j < $nrColumns; $j++) {
                $a[$i][$j] = $a[$i][$j] - $a[$step][$j]*$f;
            }
        }
        return true;
    }

    public function gaussElimination(array &$a):void {
        $nrLines = count($a);
        for ($i = 0; $i < $nrLines - 1; $i++) {
            $ok = $this->eliminationStep($a, $i);
        }
    }

    public function solution(array $a):array {
        $result = [];
        $nrLines = count($a);
        for ($i = $nrLines - 1; $i >= 0; $i--) {
            $s = 0;
            for ($j = $i + 1; $j < $nrLines; $j++) {
                $s = $s + $a[$i][$j]*$result[$j];
            }
            $result[$i] = (-$a[$i][$nrLines] - $s)/$a[$i][$i];
        }
        return $result;
    }

    private function sortVariables(array $equations):array {
        $columns = [];
        foreach ($equations as $equation) {
            foreach ($equation as $key => $value) {
                if (!in_array($key, $columns)) {
                    $columns[] = $key;
                }
            }
        }
        if (!in_array('1', $columns)) {
            $columns[] = '1';
        }
        sort($columns, SORT_STRING);
        return $columns;
    }

    /**
     * $equations is an array of equations
     * Each equation is an array indexed by variable names and by '1'. 
     * The float values of elements indexed by a variable are the coefficient of that variable, 
     * the float values of the elements indexed by '1' are constants
     * makeMatrix returns an array representing a matrix in position 0. The elements are arrays with an entry for each column
     * a[$i][$j] is the matrix element in line $i, column $j. 
     * in position 1 a vector wirth the names of the variables and '1' for constants is returned. 
     * This vector can be considered a superscript of the matrix with names for its columns.
     * 
     * @param array $equations 
     * @return array 
     */
    public function makeMatrix(array $equations):array {
        // Now the first entry of $columns has index '1' for the constants, the following are the names of variables in alphabetic order
        $columns = $this->sortVariables($equations);
        $nrLines = count($equations);
        $nrColumns = count($columns);
        for ($i = 0; $i < $nrLines; $i++) {
            // Fill the first $nrColumns - 1 columns with the values for variable coefficients
            for ($j = 1; $j < $nrColumns; $j++) {
                if (isset($equations[$i]) && isset($equations[$i][$columns[$j]])) {
                    $a[$i][$j - 1] = $equations[$i][$columns[$j]];
                } else {
                    $a[$i][$j - 1] = 0;
                }
            }
            // Fill the last columns with the values for constants
            if (isset($equations[$i]) && isset($equations[$i][$columns[0]])) {
                $a[$i][$nrColumns - 1] = $equations[$i][$columns[0]];
            } else {
                $a[$i][$nrColumns - 1] = 0;
            }
        }
        return [$a, $columns];
    }

    public function solveLinEq(array $equations):array {
        $result = [];
        $m = $this->makeMatrix($equations);
        $a = $m[0]; // The matrix proper
        $names = $m[1];
        $this->gaussElimination($a);
        $s = $this->solution($a);
        $nr = count($s);
        for ($i = 0; $i < $nr; $i++) {
            // Due to the fact that in ascii digits precede characters, '1' is the first name
            $result[$names[$i + 1]] = $s[$i];  
        }
        return $result;
    }
}