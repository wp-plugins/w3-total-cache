<?php

class Minify_YUICompressor {
    public static $jarFile = '';
    public static $javaExecutable = 'java';

    public static function minifyJs($js, $options = array()) {
        return self::_minify('js', $js, $options);
    }

    public static function minifyCss($css, $options = array()) {
        return self::_minify('css', $css, $options);
    }

    protected static function _minify($type, $content, $options) {
        $output = null;

        self::_execute($type, $options, $content, $output);

        return $output;
    }

    protected static function _execute($type, $options, $input, &$output) {
        $cmd = self::_getCmd($type, $options);
        $return = self::_run($cmd, $input, $output);

        return $return;
    }

    protected static function _getCmd($type, $options) {
        if (!is_file(self::$jarFile)) {
            throw new Exception(sprintf('JAR file (%s) is not a valid file.', self::$jarFile));
        }

        if (!is_file(self::$javaExecutable)) {
            throw new Exception(sprintf('JAVA executable (%s) is not a valid file.', self::$javaExecutable));
        }

        $options = array_merge(array(
            'line-break' => 5000,
            'type' => $type,
            'nomunge' => false,
            'preserve-semi' => false,
            'disable-optimizations' => false
        ), $options);

        $optionsString = '';

        foreach ($options as $option => $value) {
            switch ($option) {
                case 'charset':
                case 'line-break':
                case 'type':
                    if ($value) {
                        $optionsString .= sprintf('--%s %s ', $option, $value);
                    }
                    break;

                case 'nomunge':
                case 'preserve-semi':
                case 'disable-optimizations':
                    if ($value) {
                        $optionsString .= sprintf('--%s ', $option);
                    }
                    break;
            }
        }

        $cmd = sprintf('%s -jar %s %s', self::$javaExecutable, escapeshellarg(self::$jarFile), $optionsString);

        return $cmd;
    }

    protected static function _run($cmd, $input, &$output) {
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $pipes = null;
        $process = proc_open($cmd, $descriptors, $pipes);

        if (!$process) {
            throw new Exception(sprintf('Unable to open process (%s).', $cmd));
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $return = proc_close($process);

        if ($return != 0) {
            throw new Exception(sprintf('Command (%s) execution failed. Error: %s. Return code: %d.', $cmd, $error, $return));
        }

        return $return;
    }
}
