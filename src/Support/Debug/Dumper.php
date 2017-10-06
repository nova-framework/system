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
        if (class_exists(CliDumper::class)) {
            $dumper = in_array(PHP_SAPI, array('cli', 'phpdbg')) ? new CliDumper : new HtmlDumper;

            $value = with(new VarCloner())->cloneVar($value);

            $dumper->dump($value);
        } else {
            var_dump($value);
        }
    }
}
