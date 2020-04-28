<?php

namespace App\Support\mdanter;

use Mdanter\Ecc\MathAdapter;
use Mdanter\Ecc\PrivateKeyInterface;
use Mdanter\Ecc\PublicKeyInterface;
use Mdanter\Ecc\Signature;

/**
 * This class serves is an Override
 */
class PrivateKey implements PrivateKeyInterface
{

    private $publicKey;

    private $secretMultiplier;

    private $adapter;

    public function __construct(PublicKeyInterface $publicKey, $secretMultiplier, MathAdapter $adapter)
    {
        $this->publicKey = $publicKey;
        $this->secretMultiplier = $secretMultiplier;
        $this->adapter = $adapter;
    }

    /**
     *
     * @return PublicKeyInterface
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param $hash
     * @param $random_k
     * @return Signature
     * @see \Mdanter\Ecc\PrivateKeyInterface::sign()
     */
    public function sign($hash, $random_k)
    {
        do {
            $canReturn = true;
            $math = $this->adapter;
            $G = $this->publicKey->getGenerator();
            $n = $G->getOrder();
            $k = $math->mod($random_k, $n);
            $p1 = $G->mul($k);
            $r = $p1->getX();

            if ($math->cmp($r, 0) == 0) {
                throw new \RuntimeException("error: random number R = 0 <br />");
            }

            $s = $math->mod($math->mul($math->inverseMod($k, $n), $math->mod($math->add($hash, $math->mul($this->secretMultiplier, $r)), $n)), $n);

            if ($math->cmp($s, 0) == 0) {
                throw new \RuntimeException("error: random number S = 0<br />");
            }


            $sDec = $s;
            $nDec = "115792089237316195423570985008687907852837564279074904382605163141518161494337";
            $n2Dec = bcdiv($nDec, "2");
            if (bcsub($sDec, $n2Dec) > 0) {
                $random_k = $math->hexDec((string)bin2hex(openssl_random_pseudo_bytes(32)));
                $canReturn = false;
            }


        } while ($canReturn == false);
        return new Signature($r, $s);
    }
}
