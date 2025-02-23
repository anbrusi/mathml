<?php
/**
 * @abstract
 * Implements arithmetics on the set of rational numbers. This set is usually denoted Q 
 * The implementation is an extension of \isLib\LncIntegers
 * 
 * Rational numbers are quotients of integer numbers in base radix. 
 * The "digits" have values from 0 to radix - 1. Digits are themselves non negative PHP integers.
 * The digits are stored in a numeric PHP array. Element 0 is positive for positive numbers and negative for negative numbers.
 * Its absolute value is the number of digits. If it is 0, it denotes the number zero and there are no digits.
 * The digits are stored from 1 up, least significant first.
 * 
 * The rational number is a numeric array with the numerator in position 0 and the denominator in position 1
 * Denominators are always positive, the sign is the sign of the numerator
 * Rational numbers are always stored in the most simplified form. The GDC of numerator and denominator is 0.
 */
namespace isLib;

class LncRationalNumbers {

    /**
     * An instance of \isLib\LncNaturalNumbers
     */
    private $LncNaturalNumbers;
    /**
     * An instance of \isLib\LncIntegers
     */
    private $LncIntegers;

    function __construct(int $radix) {
        $this->LncIntegers = new \isLib\LncIntegers($radix);
        $this->LncNaturalNumbers = $this->LncIntegers->natNumbers();
    }

    /**
     * Reduces a rational number to its simplest form,
     * by dividing numerator and denominator by the greatest common divisor
     * 
     * @param array $u 
     * @return array 
     */
    private function rnReduce(array $u):array {
        $absNumerator = $this->LncIntegers->intAbs($u[0]);
        $gcd = $this->LncNaturalNumbers->nnGCD($absNumerator, $u[1]);
        if ($gcd[0] != 1 || $gcd[1] !=1) {
            // GCD was NOT 1, divide
            $numerator = $this->LncIntegers->intDivMod($u[0], $gcd)['quotient'];
            $denominator = $this->LncNaturalNumbers->nnDivMod($u[1], $gcd)['quotient'];
            return array($numerator, $denominator);
        } else {
            // GCD = 1, aready reduced
            return $u;
        }
    }
    /**
     * Returns a rational number from a string of the form numerator/denominator. 
     * The denominator will be positive and the fraction will be in its shortest form
     * 
     * @param string $str 
     * @return array 
     */
    public function strToRn(string $str):array {
        $parts = explode('/', $str);
        if (count($parts) != 2) {
            throw new \Exception('Illegal string format for a rational number: '.$str);
        }
        $numerator = $this->LncIntegers->strToInt(trim($parts[0]));
        $denominator = $this->LncIntegers->strToInt(trim($parts[1]));
        if ($denominator[0] == 0) {
            throw new \Exception('The denominator cannot be zero');
        }
        if ($denominator[0] < 0) {
            $this->LncIntegers->intChgSign($numerator);
            $this->LncIntegers->intChgSign($denominator);
        }
        return $this->rnReduce(array($numerator, $denominator));
    }

    /**
     * Returns a string representation of $u like -12/98 
     * 
     * @param array $u 
     * @return string 
     */
    public function rnToStr(array $u):string {
        return $this->LncIntegers->intToStr($u[0]).'/'.$this->LncNaturalNumbers->nnToStr($u[1]);
    }
    /**
     * Returns a rational number as numerator "/" denominator
     * @param array $u 
     * @return string 
     */
    public function showRn(array $u):string {
        return $this->LncIntegers->showInt($u[0]).'/'.$this->LncNaturalNumbers->showNn($u[1]);
    }

    /**
     * Let $u = a/b and $v = c/d. Then the result is r=(ad+cb)/bd.
     * Let g = GCD(b,d). Dividing numerator and denominator of r by g we get r=(a(d/g) + c(b/g)) / ((b/g)d).
     * Since g is a divisor of both b and d, the fractions (d/g) and (b/g) are natural numbers
     * Define s=b/g and t=d/g. Then r=(at+cs) / (sd)
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rnAdd(array $u, array $v):array {
        $g = $this->LncNaturalNumbers->nnGCD($u[1],$v[1]); // GCD of denominators = GCD(b,d)
        if ($g[0] == 1 && $g[1] == 1) {
            // Special case, where $g is one. Statistically 61% of cases, so treat it apart to improve speed. 
            $ad = $this->LncIntegers->intMult($u[0], $v[1]); // ad
            $cb = $this->LncIntegers->intMult($v[0], $u[1]); // cb
            $numerator = $this->LncIntegers->intAdd($ad, $cb); // ad + cb
            $denominator = $this->LncNaturalNumbers->nnMult($u[1], $v[1]); // bd
        } else {
            $s = $this->LncNaturalNumbers->nnDivMod($u[1], $g)['quotient']; // b / GCD(b,d)
            $t = $this->LncNaturalNumbers->nnDivMod($v[1], $g)['quotient']; // d / GCD(b,d)
            $at = $this->LncIntegers->intMult($u[0], $t); // ad / GCD(b,d)
            $cs = $this->LncIntegers->intMult($v[0], $s); // cb / GCD(b,d)
            $numerator = $this->LncIntegers->intAdd($at, $cs); // ad / GCD(b,d) + cb / GCD(b,d)
            $denominator = $this->LncNaturalNumbers->nnMult($s, $v[1]); // bd / GCD(b,d)
        }
        $result = [$numerator, $denominator]; // (ad / GCD(b,d) + cb / GCD(b,d)) / (bd / GCD(b,d))
        // The above reductions do not guarantee a reduced result as can be seen by 2/3 + 7/3 = 9/3
        return $this->rnReduce($result);
    }

    /**
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rnSub(array $u, array $v):array {
        $this->LncIntegers->intChgSign($v[0]); // Change the sign of the numerator of $v
        // Note that rnAdd performs a final reduction, so here no reduction is required
        return $this->rnAdd($u,$v);
    }

    public function rnChgSign(array &$u) {
        // Change sign of the numerator
        $this->LncIntegers->intChgSign($u[0]);
    }

    /**
     * Let $u = a/b and $v = c/d The unreduced result is r=ac/bd. 
     * We could compute this and call $this->rnReduce, but it is more efficient to note
     * that GCD(ac,bd) = GCD(a,d)GCD(b,c) and reduce in place, because the numbers involved are smaller
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rnMult(array $u, array $v):array {
        $aAbsolute = $this->LncIntegers->intAbs($u[0]);
        $cAbsolute = $this->LncIntegers->intAbs($v[0]);
        $gad = $this->LncNaturalNumbers->nnGCD($aAbsolute, $v[1]);
        $gbc = $this->LncNaturalNumbers->nnGCD($u[1], $cAbsolute);
        $g = $this->LncNaturalNumbers->nnMult($gad, $gbc);
        $numerator = $this->LncIntegers->intMult($u[0], $v[0]);
        $denominator = $this->LncNaturalNumbers->nnMult($u[1], $v[1]);
        if ($g[0] != 1 || $g[1] != 1) {
            // Reduction is needed
            $numerator = $this->LncIntegers->intDivMod($numerator, $g)['quotient'];
            $denominator = $this->LncNaturalNumbers->nnDivMod($denominator, $g)['quotient'];
        }
        return [$numerator, $denominator];
    }

    /**
     * Flips numerator and denominator, taking care of keeping the denominator positive
     * 
     * @param array $u 
     * @return array 
     */
    private function rnReciprocal(array $u):array {
        $pivot = $u[0];
        $u[0] = $u[1];
        $u[1] = $pivot;
        if ($u[1][0] < 0) {
            $this->LncIntegers->intChgSign($u[0]);
            $this->LncIntegers->intChgSign($u[1]);
        }
        return $u;
    }

    /**
     * Divides $u by $v
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rnDiv(array $u, array $v):array {
        $v = $this->rnReciprocal($v);
        return $this->rnMult($u, $v);
    }

    /**
     * Returns the rational number 0. In fact this is 0/1 to conform to our representation of rational numbers
     * @return array 
     */
    public function rnZero():array {
        $numerator = $this->LncNaturalNumbers->nnZero();
        $denominator = $this->LncNaturalNumbers->nnOne();
        return array($numerator, $denominator);
    }

    /**
     * Returns true iff the numerator of $u is '0'
     * 
     * @param array $u 
     * @return bool 
     */
    public function rnIsZero(array $u):bool {
        return ($u[0][0] == 1 && $u[0][1] == '0');
    }

    /**
     * Returns the rational number 1. In fact this is 1/1 to conform to our representation of rational numbers
     * 
     * @return array 
     */
    public function rnOne():array {
        $nnOne = $this->LncNaturalNumbers->nnOne(); // This is the natural number 1
        return array($nnOne, $nnOne);
    }

    public function isOne(array $u):bool {
        return ($u[0][0] == 1 && $u[0][1] == 1 && $u[1][0] == 1 && $u[1][1] == 1);
    }

    /**
     * Returns an integer power of a rational number. Negative bases and negative exponents are allowed.
     * The only case throwing an exception is zero raised to a negative exponent
     * 
     * @param array $u 
     * @param int $n 
     * @return array 
     * @throws Exception 
     */
    public function rnPower(array $u, int $n):array {
        if ($n < 0) {
           $n = -$n;
           $negativeExponent = true;
           if ($u[0][0] == 0) {
               // No negative power of zero
               \isLib\LmathError::setError(\isLib\LmathError::ORI_NC_RATIONALNUMBERS, 1);
           }
        } else {
            $negativeExponent = false;
        }
        if ($u[0][0] < 0) {
            $u[0] = $this->LncIntegers->intAbs($u[0]); // Take the absolute value of the base
            $negativeBase = true;
        } else {
            $negativeBase = false;
        }
        $result = $this->rnOne();
        $currPower = $u;
        while ($n > 0) {
            if ($n & 1) {
                // The least significant bit is set, so multiply by the current power
                $result = $this->rnMult($result, $currPower);
            }
            $n = $n >> 1;
            // The current power is the square of the previous
            $currPower = $this->rnMult($currPower, $currPower);
        }
        if ($negativeExponent) {
            $result = $this->rnReciprocal($result);
        }
        if ($negativeBase) {
            $this->LncIntegers->intChgSign($result[0]); // Change sign of numerator of result
        }
        return $result;
    }

    /**
     * $u and $v are rational numbers as encoded in this class. 
     * rnCmp returns +1, if $u > $v, -1 if $u < $v and 0 if $u = $v
     */
    public function rnCmp(array $u, array $v):int {
        $diff = $this->rnSub($u, $v);
        if ($this->rnIsZero($diff)) {
            return 0;
        }
        if ($diff[0][0] < 0) {
            return -1;
        }
        return 1;
    }

    /**
     * Rational numbers are a field, so the concept of a GCD of two rational numbers may seem strange.
     * Here we use the convantion that v is a divisor of u only if u is an INTEGER multiple of v.
     * The voyage 200 calculator returns gcd(3/7,12/22) = 3/77 because 11*3/77 = 3/7 and 14*3/77 = 6/11 = 12/22
     * It appears that Wolfram alpha used to give this result some time ago. It does not now 23.02.2025
     * 
     * @param array $u 
     * @param array $v 
     * @return array 
     */
    public function rnGCD(array $u, array $v):array {
        if ($this->rnCmp($u, $v) < 0) {
            $w = $u;
            $u = $v;
            $v = $w;
        }
        while (!$this->rnIsZero($v)) {
            $rationalQuotient = $this->rnDiv($u, $v);
            // Take the interger part of $rationalQuotien
            $intQuotient = $this->LncIntegers->intDivMod($rationalQuotient[0], $rationalQuotient[1])['quotient'];
            // Multiply $v by $intQuotient. This is done by multiplying numerators only
            $subtrahend = [$this->LncIntegers->intMult($v[0], $intQuotient), $v[1]];
            $r = $this->rnSub($u, $subtrahend);
            $u = $v;
            $v = $r;
        }
        return $u;
    }
}