<?php

namespace isLib;

class LncRatPolynomials {

    private \isLib\LncRationalNumbers $LncRationalNumbers;

    function __construct(int $radix) {
        $this->LncRationalNumbers = new \isLib\LncRationalNumbers($radix);
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
        $nrMonomials = count($matches[0]);
        $degree = $matches[3][0]; // We suppose that $strpoly is ordereed by powers of x
        $polynomial = [];
        for ($i = $degree; $i >= 0; $i--) {
            // Get the index in $matches of the monomial with exponent $i
            $j = 0;
            $index = -1; // Sentinel
            while ($j < $nrMonomials) {
                $a = $matches[3][$j];
                if ($a == $i) {
                    $index = $j;
                    break;
                }
                $j += 1;
            }
            if ($index >= 0) {
                $sign = $matches[1][$index];
                if ($sign == '-') {
                    $coefficient = '-'.$matches[2][$index];
                } else {
                    $coefficient = $matches[2][$index];
                }
            } else {
                // there is no term with exponen $i
                $sign = '+';
                $coefficient = '0/1';
            }
            $polynomial[] = $this->LncRationalNumbers->strToRn($coefficient);
        }        
        return $polynomial;
    }

    public function showRp(array $rp):string {
        $rep = '';
        for ($i = 0; $i < count($rp); $i++) {
            $rep .= $this->LncRationalNumbers->showRn($rp[$i])."\n";
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
}