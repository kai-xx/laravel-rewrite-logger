<?php


namespace App\Console;


use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = 'info', $verbosity = null)
    {
        app('cLog')->info($string);
        parent::line($string, $style, $verbosity);
    }
    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        app('cLog')->error($string);
        parent::error($string, $verbosity);
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        app('cLog')->info($string);
        parent::info($string, $verbosity);
    }
}
