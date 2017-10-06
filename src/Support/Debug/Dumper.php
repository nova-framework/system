<?php

namespace Nova\Support\Debug;

use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;


class Dumper
{

    /**
     * Dump a value with elegance.
     *
     * @param  mixed  $value
     * @return void
     */
    public function dump($value)
    {
        $cloner = new VarCloner();

        if (in_array(PHP_SAPI, array('cli', 'phpdbg'))) {
            $dumper = new CliDumper();
        } else {
            $dumper = new HtmlDumper();
        }

        $dumper->dump($cloner->cloneVar($value));
    }
}
