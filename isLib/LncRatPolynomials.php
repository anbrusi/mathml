<?php

/**
 * @abstract
 * Rational polynomes are represented by numeric arrays of numeric arrays with two entries.
 * Entry 0 is the exponent of the variable, entry 1 the coefficient, which is a rational number as defined in LncRationalNumbers
 */
namespace isLib;

class LncRatPolynomials {

    private \isLib\LncRationalNumbers $LncRationalNumbers;

    function __construct(int $radix) {
        $this->LncRationalNumbers = new \isLib\LncRationalNumbers($radix);
    }

    /**
     * Returns a normalized polynomial from a list of monomials
     * A polynomial is normalized if the array is ordered in descending order of exponent,
     * no exponent is repeated and there are no leading zeros, except for a single constant zero.
     * 
     * @param array $monolist 
     * @return array 
     */
    private function normalize(array $monolist):array {
        // Sort the monomials in order of decreasing exponents
        usort($monolist, function($a, $b) {return $b[0] - $a[0];});
        // Consolidate monomials with equal exponent
        $polynomial = [];
        $i = 0;
        while ($i < count($monolist)) {
            $monomial = $monolist[$i];
            while ($i < count($monolist) && $monolist[$i][0] == $monolist[$i + 1][0]) {
                $monomial[1] = $this->LncRationalNumbers->rnAdd($monomial[1], $monolist[$i + 1][1]);
                $i++;
            }
            $polynomial[] = $monomial;
            $i++;
        }
        // Now polynomial is ordered in descending powers and has no two monomials of the same power. Strip leading zeros
        $maxZero = -1; // sentinel 
        for ($i = 0; $i < count($polynomial) - 1; $i++) {
            if ($this->LncRationalNumbers->isZero($polynomial[$i][1])) {
                $maxZero = $i;
            } else {
                break;
            }
        }
        $clean = [];
        for ($i = $maxZero + 1; $i < count($polynomial); $i++) {
            $clean[] = $polynomial[$i];
        }
        return $clean;
    }

    /**
     * The string representation of the polynomial in $strpoly has the form
     * +1/3x^3-2/7x^1+4/1x^0 All coefficients are explicit rational numbers, no x is omitted
     * and all x are followed by '^' natural number
     * 
     * @param string $strpoly 
     * @return string 
     */
    public function strToRp(string $strpoly):array {
        preg_match_all('/([+,-])([\d]+\/[\d]+)x\^([\d]+)/', $strpoly, $matches);
        // $matches[0] holds the monomials followed by an addop (The last addop is empty)
        // $matches[1] holds the sign of the following monomial
        // $matches[2] holds the rational coefficients
        // $matches[3] holds the exponents of x

        
        $nr = count($matches[0]); // All $matches[i] hold the same number $nr of elements/
        // Compute all coefficients
        for ($i = 0; $i < $nr; $i++) {
            if ($matches[1][$i] == '-') {
                $sign = '-';
            } else {
                $sign = '';
            }
            $coefficients[$i] = $this->LncRationalNumbers->strToRn($sign.$matches[2][$i]);
        }
        // Build an unordered list of monomials
        $monolist = [];
        for ($i = 0; $i < $nr; $i++) {
            $monolist[] = [$matches[3][$i], $coefficients[$i]];
        }
        return $this->normalize($monolist);
    }

    public function showRp(array $rp):string {
        $rep = '';
        for ($i = 0; $i < count($rp); $i++) {
            $rep .= $this->LncRationalNumbers->showRn($rp[$i][1]).' (x^'.$rp[$i][0].')'."\n";
        }
        return $rep;
    }

    public function rpToStr(array $rp):string {
        $str = '';
        for ($i = 0; $i < count($rp); $i++) {
            $coef = $this->LncRationalNumbers->rnToStr($rp[$i]);
            $negative = false;
            $zero = false;
            if ($coef[0] == '-') {
                $negative = true;
                $coef = substr($coef, 1);
            }
            if ($coef[0] == '0') {
                $zero = true;
            }
            if (!$zero) {
                if ($negative) {
                    $str .= '-';
                } else {
                    if ($i > 0) {
                        $str .= '+';
                    }
                }
                $exponent = count($rp) - 1 - $i;
                if ($exponent == 1) {
                    $power = 'x';
                } elseif ($exponent > 1) {
                    $power = 'x^'.$exponent;
                } else {
                    $power = '';
                }
                // Purify coefficient
                $slashpos = strpos($coef, '/');
                $denominator = substr($coef, $slashpos + 1);
                if ($denominator == '1') {
                    $coef = substr($coef, 0, strlen($coef) - 2); 
                }
                $str .= $coef.$power;
            }
        }
        return $str;
    }

    public function rpAdd(array $u, array $v):array {
        if (count($u) < count($v)) {
            $w = $u;
            $u = $v;
            $v = $w;
        }
        // Add $v into $u
        $shift = count($u) - count ($v);
        for ($i = count($v) - 1; $i >= 0; $i--) {
            $u[$i + $shift] = $this->LncRationalNumbers->rnAdd($u[$i + $shift], $v[$i]);
        }
        return $u;
    }

    public function rpChgSign(array &$u) {
        for ($i = 0; $i < count($u); $i++) {
            $this->LncRationalNumbers->rnChgSign($u[$i]);
        }
    }

    public function rpSub(array $u, array $v): array {
        $this->rpChgSign($v);
        return $this->rpAdd($u, $v);
    }
}