<?php

namespace Lijinma;

class Commander
{
    public $v;

    /**
     * @var Option[] array
     */
    public $_options = [];

    public $_rawArgv;

    public $_args;

    public $_name;

    function __construct()
    {
        array_push($this->_options, new Option('-h, --help', 'Output usage information'));

        set_exception_handler([$this, 'exception']);
    }

    /**
     * @param $key
     * @param $value
     * @throws \Exception
     */
    public function createProperty($key, $value)
    {
        if (!property_exists($this, $key)) {
            $this->$key = $value;
        } else {
            throw new \Exception(sprintf("'%s' exists as a property in the Commander, please use other property", $key));
        }
    }


    /**
     * @param $v
     * @return $this
     */
    public function version($v)
    {
        $this->v = $v;
        array_push($this->_options, new Option('-v, --version', 'Output version information'));
        return $this;
    }

    /**
     * @param $flags
     * @param $desc
     * @return $this
     */
    public function option($flags, $desc)
    {
        array_push($this->_options, new Option($flags, $desc));
        return $this;
    }

    /**
     * @param $argv
     */
    public function parse($argv)
    {
        $this->_rawArgv = $argv;

        $this->_name = $argv[0];

        $this->_args = $this->normalize(array_slice($argv, 1));

        $this->parseOptions($this->_args);
    }

    /**
     * @param $args
     * @return array
     */
    public function normalize($args)
    {
        $ret = [];

        foreach ($args as $arg) {

            /**
             * --abc
             */
            if (strlen($arg) > 2 && $arg[0] === '-' && $arg[1] !== '-') {
                $ret = array_merge($ret, $this->splitJoinedShortFlags($arg));
            } /**
             * --longflag=assignment
             */
            elseif (strlen($arg) > 2 && preg_match('/^--/', $arg)) {
                $ret = array_merge($ret, $this->splitLongFlagAndAssignment($arg));
            } else {
                array_push($ret, $arg);
            }


        }

        return $ret;
    }

    /**
     * @param $arg
     * @return mixed
     */
    public function splitJoinedShortFlags($arg)
    {
        $ret = [];
        $newArgs = str_split(substr($arg, 1, strlen($arg) - 1));
        foreach ($newArgs as $n) {
            array_push($ret, '-' . $n);
        }
        return $ret;
    }

    /**
     * @param $arg
     * @return array
     */
    public function splitLongFlagAndAssignment($arg)
    {
        $ret = [];
        if (strpos($arg, '=') !== false) {
            foreach (explode('=', $arg) as $a) {
                array_push($ret, $a);
            }
            return $ret;
        } else {
            array_push($ret, $arg);
            return $ret;
        }
    }

    /**
     * @param $arg
     * @return bool|Option
     */
    public function optionFor($arg)
    {
        /**
         * @var Option $option
         */
        foreach ($this->_options as $option) {
            if ($option->is($arg)) {
                return $option;
            }
        }

        return false;
    }

    /**
     * @param $args
     * @throws \Exception
     */
    public function parseOptions($args)
    {
        for ($i = 0; $i < count($args); $i++) {
            $option = $this->optionFor($args[$i]);
            if (!$option) {
                if (strlen($args[$i]) > 1 && $args[$i][0] == '-') {
                    throw new \Exception(sprintf("error: unknown option `%s'", $args[$i]));
                }
            } else {
                $nextArg = isset($args[$i + 1]) ? $args[$i + 1] : null;

                if (($option->required && $nextArg[0] === '-')
                    || ($option->required && !$nextArg)
                ) {
                    throw new \Exception(sprintf("error: option `%s' argument missing", $option->getName()));
                }

                if ($nextArg[0] === '-') {
                    $this->triggerOption($option);
                } else {
                    $this->triggerOption($option, $nextArg);
                    $i++;
                }
            }
        }
    }

    /**
     * @param $option
     * @param string $value
     */
    public function triggerOption($option, $value = '')
    {
        if ($option->getName() == 'help') {
            $this->outputHelp();
        } elseif ($option->getName() == 'version') {
            $this->outputVersion();
        } else {
            $this->createProperty($option->getName(), $value);
        }
    }

    public function outputVersion()
    {
        $version = $this->_name . ' version ' . $this->v . PHP_EOL;

        echo $version;

    }

    public function outputHelp()
    {
        $help = PHP_EOL;

        $help .= Color::YELLOW . '  Usage: ' . $this->_name . ' ' . $this->usage() . PHP_EOL;

        $help .= PHP_EOL;

        $help .= '  Options:' . PHP_EOL;

        $help .= PHP_EOL;

        $help .= implode($this->getOptionHelp(), PHP_EOL) . PHP_EOL;

        $help .= PHP_EOL;

        echo $help;
    }


    /**
     * @return int|mixed
     */
    public function getLargestOptionWidth()
    {
        $max = 0;

        /**
         * @var Option $option
         */
        foreach ($this->_options as $option) {
            $max = max(strlen($option->rawFlags), $max);
        }

        return $max;
    }

    /**
     * @param $str
     * @param $width
     * @return string
     */
    public function pad($str, $width)
    {
        return $str . str_repeat(' ', $width - strlen($str));
    }

    /**
     * @return array
     */
    public function getOptionHelp()
    {
        $ret = [];

        $width = $this->getLargestOptionWidth();

        foreach ($this->_options as $option) {
            array_push($ret, Color::GREEN . '    ' . $this->pad($option->rawFlags, $width) . '  ' . Color::WHITE .$option->desc);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function usage()
    {
        //todo for multiple commands
        return '[options]';
    }

    /**
     * @param $exception
     */
    public function exception($exception)
    {
        $message = PHP_EOL;
        $message .= Color::BG_RED . $exception->getMessage() . PHP_EOL;
        $message .= PHP_EOL;
        echo $message;
        exit();
    }
}
