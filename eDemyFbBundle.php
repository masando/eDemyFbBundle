<?php

namespace eDemy\FbBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class eDemyFbBundle extends Bundle
{
    public static function getBundleName($type = null)
    {
        if ($type == null) {

            return 'eDemyFbBundle';
        } else {
            if ($type == 'Simple') {

                return 'Fb';
            } else {
                if ($type == 'simple') {

                    return 'fb';
                }
            }
        }
    }

    public static function eDemyBundle() {

        return true;
    }

}
