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

    public function gaussElimination(array &$a):void {
        $nrLines = count($a);
        if ($nrLines > 0) {
            $nrColumns = count($a[0]);
            $column = 0;
            $line = 0;
            while ($line < $nrLines - 1 && $column < $nrColumns - 1) {
                $pivotLine = $this->getPivot($a, $line, $column);
                $pivot = $a[$pivotLine][$column];
                if ($this->isZero($pivot)) {
                    // No exchange needed
                    $column++;
                } else {
                    if ($pivotLine != $line) {
                        $this->switchLines($a, $line, $pivotLine);
                    }
                    // Subtract a suitable multiple of $line, which now is the pivot line, from all lines below
                    for ($i = $line + 1; $i < $nrLines; $i++) {
                        $factor = $a[$i][$column]/$pivot;
                        for ($j = $column; $j < $nrColumns; $j++) {
                            $a[$i][$j] = $a[$i][$j] - $factor * $a[$line][$j];
                        }
                    }
                    $line++;
                    $column++;
                }
            }
        }        
    }

    /**
     * Returns the rank of a matrix, AFTER COMPLETION of a Gauss elimination.
     * The rank is the number of lines in the principal part (the part before the constant column), which do not have only 0 entries
     * 
     * @param array $a 
     * @return int 
     */
    public function rank(array $a):int {
        $rank = 0;
        $nrLines = count($a);
        if ($nrLines > 0) {
            $nrColumns = count($a[0]);
            for ($i = 0; $i < $nrLines; $i++) {
                for ($j = 0; $j < $nrColumns - 1; $j++) {
                    if (!$this->isZero($a[$i][$j])) {
                        $rank += 1;
                        break;
                    }
                }
            }
        }
        return $rank;
    }

    /**
     * If the rank is equal to the number of lines, or if it is smaller, but the additional lines are fullfilled compatibility conditions,
     * i.e. consist of zeros only, the system has a unique solution, which can be determined by a regular backwards substitution.
     * 
     * regularBackuSubst returns a numeric array of the values of the variables in the order in which they are associated to columns of $a
     * 
     * @param int $rank 
     * @return array 
     */
    public function regularBackSubst(array $a, int $rank):array {
        $result = [];         
        $nrLines = count($a);
        if ($nrLines > 0) {
            $nrColumns = count($a[0]);
            for ($i = $rank - 1; $i >= 0; $i--) {
                $nsum = 0;
                for ($j = $i + 1; $j < $nrColumns - 1; $j++) {
                    $nsum += $a[$i][$j] * $result[$j];
                }
                $result[$i] = (-$a[$i][$nrColumns - 1] - $nsum)/$a[$i][$i];
            }
        }
        return $result;
    }

    public function backSubstitution(array $a, int $rank, array $names):array {
        $nresult = []; 
        $sresult = [];      
        $nrLines = count($a);
        if ($nrLines > 0) {
            $nrColumns = count($a[0]);
            if ($rank == $nrColumns - 1) {
                // Pivots are in the diagonal of the first $nrLines. Use a simplified fully numeric substitution 
                for ($i = $rank - 1; $i >= 0; $i--) {
                    $nsum = 0;
                    for ($j = $i + 1; $j < $nrColumns - 1; $j++) {
                        $nsum += $a[$i][$j] * $nresult[$j];
                    }
                    $nresult[$i] = (-$a[$i][$nrColumns - 1] - $nsum)/$a[$i][$i];
                }
            } else {
                // Pivots are in stair form but with unequal step lengths. Use a symbolic approach
                $pivotColumns = []; 
                for ($i = 0; $i < $rank; $i++) {
                    $j = 0;
                    while ($this->isZero($a[$i][$j]) && $j < $nrColumns - 2) {
                        $j += 1;
                    }
                    $pivotColumns[$i] = $j;
                }
                // Get free variables and set to to zero their value in $nresults and set their name in $sresults
                // The array $nresult will yeld a solution in which all free variables are 0.
                $free = [];
                for ($j = 0; $j < $nrColumns - 1; $j++) {
                    if (!in_array($j, $pivotColumns)) {
                        $free[] = $j;
                    }
                }
                for ($i = $rank - 1; $i >= 0; $i--) {
                    $ssum = '';
                    for ($j = $pivotColumns[$i] + 1; $j < $nrColumns - 1; $j++) {
                        if (!$this->isZero($a[$i][$j])) {
                            $numFactor = $a[$i][$j] / $a[$i][$pivotColumns[$i]];
                            if ($numFactor >= 0) {
                                $sign = '+';
                            } else {
                                $sign = '';
                            }
                            $ssum .= $sign . strval($numFactor) . $names[$j + 1];
                        }
                    }
                    $const = strval(-$a[$i][$nrColumns - 1] / $a[$i][$pivotColumns[$i]]);
                    $sresult[$i] = $names[$pivotColumns[$i] + 1].'='.$const.$ssum;
                }
            }
        }
        if (!empty($nresult)) {
            return $nresult;
        } else {
            return $sresult;
        }
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
     * 
     * makeMatrix returns an array representing a matrix in position 0. 
     *      The elements are arrays with an entry for each column
     *      a[$i][$j] is the matrix element in line $i, column $j. 
     * in position 1 a vector wirth the names of the variables and '1' for constants is returned. 
     *      This vector can be considered a superscript of the matrix with names for its columns.
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

    /**
     * If there is a solution (even if the system is overspecified) an array indexed by variable names is returned,
     * else (intrinsic contradiction) an empty array is returned
     * 
     * @param array $equations 
     * @return array 
     */
    public function solveLinEq(array $equations):array {
        $result = [];
        $m = $this->makeMatrix($equations);
        $a = $m[0]; // The matrix proper
        $names = $m[1];
        $this->gaussElimination($a);
        $rank = $this->rank($a);
        $nrLines = count($a);
        if ($rank < $nrLines) {
            // System overspecified. Check compatibility conditions
            $compatible = true;
            $nrColumns = count($a[0]);
            for ($i = $nrLines - 1; $i > $rank - 1; $i--) {
                if (!$this->isZero($a[$i][$nrColumns - 1])) {
                    $compatible = false;
                    break;
                }
            }
        } else {
            $compatible = true;
        }
        if ($compatible) {
            $nrColumns = count($a[0]);
            if ($rank == $nrColumns - 1) {
                // rank = number of variables. The part of the matrix above zero lines is a quadratic upper diagonal matrix 
                $s = $this->regularBackSubst($a, $rank);
                for ($i = 0; $i < $nrColumns - 1; $i++) {
                    // Due to the fact that in ascii digits precede characters, '1' is the first name
                    $result[$names[$i + 1]] = $s[$i];  
                }
                $type = 0;
            } else {
                $result = $this->backSubstitution($a, $rank, $names);
                $type = 1;
            }
        }
        return [$result, $type];
    }
}