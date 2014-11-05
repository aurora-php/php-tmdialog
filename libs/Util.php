<?php

/*
 * This file is part of the 'octris/php-tmdialog' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\TMDialog;

/**
 * Static utility class.
 *
 * @copyright   copyright (c) 2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Util
{
    /**
     * Execute a shell command in stdin/stdout pipe context.
     *
     * @param   string          $cmd            Command to execute.
     * @param   string          $inp            Optional input.
     * @param   bool            $stderr         Whether to concat stderr to output.
     * @return  string                          Output of command.
     */
    public static function pipeCmd($cmd, $inp = null, $stderr = false)
    {
        $p_descriptors = array(
           0 => array("pipe", "r"),  // stdin
           1 => array("pipe", "w"),  // stdout
           2 => ($stderr ? array('pipe', 'w') : array("file", "/dev/null", "w"))    // stderr
        );

        $p_options = array(
            'suppress_errors' => true,
            'bypass_shell' => true
        );

        $p_pipes = array();
        $p_cwd = null;

        $proc = proc_open($cmd, $p_descriptors, $p_pipes, $p_cwd, $p_options);
        $out  = false;

        if (is_resource($proc)) {
            if (!is_null($inp)) {
                fwrite($p_pipes[0], $inp);
            }

            fclose($p_pipes[0]);

            $out = stream_get_contents($p_pipes[1]);
            fclose($p_pipes[1]);

            if ($stderr) {
                $out .= "\n" . stream_get_contents($p_pipes[2]);
                fclose($p_pipes[2]);
            }

            proc_close($proc);
        }

        return $out;
    }

    /**
     * Convert rtf string to a plain text string.
     *
     * @param   string          $inp            Rtf string.
     * @return  string                          Plain text string.
     */
    public static function rtf2txt($inp)
    {
        return self::pipeCmd(
            'textutil -convert txt -format rtf -stdin -stdout',
            $inp
        );
    }

    /**
     * Convert a plain text string to a rtf string.
     *
     * @param   string          $inp            Plain text string.
     * @return  string                          Rtf string.
     */
    public static function txt2rtf($inp)
    {
        return self::pipeCmd(
            'textutil -convert rtf -format txt -stdin -stdout',
            $inp
        );
    }
}
