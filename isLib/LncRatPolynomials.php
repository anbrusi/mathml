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
     * no exponent is repeated and there are no monomials with zero coefficient, except for monomials of power 0.
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
            while ($i < count($monolist) - 1 && $monolist[$i][0] == $monolist[$i + 1][0]) {
                $monomial[1] = $this->LncRationalNumbers->rnAdd($monomial[1], $monolist[$i + 1][1]);
                $i++;
            }
            $polynomial[] = $monomial;
            $i++;
        }
        // Now polynomial is ordered in descending powers and has no two monomials of the same power
        // Remove all zero monomials. Only the zero polynomial has a zero monomial (the constant monomial zero)
        $clean = [];
        for ($i = 0; $i < count($polynomial); $i++) {
            if (!$this->LncRationalNumbers->rnIsZero($polynomial[$i][1])) {
                $clean[] = $polynomial[$i];
            }
        }
        if (count($clean) == 0) {
            // Everything was removed, it was the zero polynomial
            $clean[] = $this->rpZeroMonomial();
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

    private function rpZeroMonomial():array {
        return [0, $this->LncRationalNumbers->rnZero()];
    }

    /**
     * Checks if the monomial $u is the constant zero
     * 
     * @param array $u 
     * @return bool 
     */
    private function rpIsZeroMonomial(array $u):bool {
        // Power 0 and rational coefficient zero.
        return ($u[0] == 0 && $this->LncRationalNumbers->rnIsZero($u[1]));
    }

    private function rpZeroPolynomial():array {
        return [$this->rpZeroMonomial()];
    }

    private function rpIsZeroPolynomial(array $u):bool {
        return ($u[0][0] == 0 && $this->LncRationalNumbers->rnIsZero($u[0][1]));
    }

    private function rpOneMonomial():array {
        return [0, $this->LncRationalNumbers->rnOne()];
    }

    private function rpIsOneMonomial(array $u):bool {
        return ($u[0] == 0 && $this->LncRationalNumbers->isOne($u[1]));
    }

    private function onePolynomial():array {
        return [$this->rpOneMonomial()];
    }

    private function rpIsOnePolynomial(array $u):bool {
        return ($u[0][0] == 0 && $this->LncRationalNumbers->isOne($u[0][1]));
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
            $coef = $this->LncRationalNumbers->rnToStr($rp[$i][1]);
            $negative = false;
            if ($coef[0] == '-') {
                $negative = true;
                $coef = substr($coef, 1);
            }
            if ($negative) {
                $str .= '-';
            } else {
                if ($i > 0) {
                    $str .= '+';
                }
            }
            $exponent = $rp[$i][0];
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
        return $str;
    }

    /**
     * The strategy is to merge $u and $v to get an unordered list of monomials, which is then normalized to a polynomial
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rpAdd(array $u, array $v):array {
        return $this->normalize(array_merge($u,$v));
    }

    public function rpChgSign(array &$u) {
        for ($i = 0; $i < count($u); $i++) {
            $this->LncRationalNumbers->rnChgSign($u[$i][1]);
        }
    }

    public function rpSub(array $u, array $v):array {
        $this->rpChgSign($v);
        return $this->rpAdd($u, $v);
    }

    /**
     * Multiplies the polynomial $u by the monomial $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    private function multByMonomial(array $u, array $v):array {
        $result = [];
        for ($i = 0; $i < count($u); $i++) {
            $result[] = [$u[$i][0] + $v[0], $this->LncRationalNumbers->rnMult($u[$i][1], $v[1])];
        }
        return $result;
    }

    /**
     * Multiplies polynomilal $u by all monomials of polynomial $v
     * The resulting list of monomials is normalized to a polynomial
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rpMult(array $u, array $v):array {
        $monolist = [];
        for ($i = 0; $i < count($v); $i++) {
            $monolist = array_merge($monolist, $this->multByMonomial($u, $v[$i]));
        }
        return $this->normalize($monolist);
    }

    /**
     * Returns the degree of a normalized polynomial i.e. the degree of the first monomial
     * 
     * @param array $u a polynomial
     * @return int 
     */
    private function degree(array $u):int {
        return $u[0][0];
    }

    public function rpDivMod(array $u, array $v):array {
        $dividend = $u;
        $quotient = [];
        $remainder = $dividend;
        while ($this->degree($dividend) >= $this->degree($v) && !$this->LncRationalNumbers->rnIsZero($dividend[0][1])) {
            // Compute a quotient monomial between the leading dividend monomial and the leading divisor monomial
            $q = [$dividend[0][0] - $v[0][0], $this->LncRationalNumbers->rnDiv($dividend[0][1], $v[0][1])];
            $p = $this->rpMult($v, [$q]);
            $dividend = $this->rpSub($dividend, $p);
            $quotient[] = $q;
            $remainder = $dividend;
        }
        return ['quotient' => $quotient, 'remainder' => $remainder];
    }

    /**
     * Returns a monic polynomial from polynomial $u, by dividing all coefficients by the leading coefficient
     * 
     * @param array $u 
     * @return array 
     */
    public function toMonic(array $u):array {
        $leadingCoeff = $u[0][1];
        $result = [];
        for ($i = 0; $i < count($u); $i++) {
            $result[] = [$u[$i][0], $this->LncRationalNumbers->rnDiv($u[$i][1], $leadingCoeff)];
        }
        return $result;
    }

    /**
     * Assumes thar $u and $v are monic polynomials. It does not check this.
     * Returns the GCD ou $u and $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rpMonicGCD(array $u, array $v):array {
        while ($this->degree($v) > 0) {
            $r = $this->rpDivMod($u, $v)['remainder'];
            $u = $v;
            if ($this->rpIsZeroPolynomial($r)) {
                return $u;
            }
            $v = $this->toMonic($r);
        }
        return $this->onePolynomial();
    }

    /**
     * Returns the GCD of polynomials $u and äv
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rpGCD(array $u, array $v):array {
        $monic = true;
        $cu = $u[0][1];
        if (!$this->LncRationalNumbers->isOne($cu)) {
            $u = $this->toMonic($u);
            $monic = false;
        }
        $cv = $v[0][1];
        if (!$this->LncRationalNumbers->isOne($cv)) {
            $v = $this->toMonic($v);
            $monic = false;
        }
        $monicGCD = $this->rpMonicGCD($u,$v);
        if ($monic) {
            return $monicGCD;
        }
        $contentGCD = $this->LncRationalNumbers->rnGCD($cu, $cv);
        return $this->multByMonomial($monicGCD, [0, $contentGCD]);
    }
}