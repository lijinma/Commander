<?php

namespace Lijinma;

class Commander
{
    public $v;

    public $options = [];

    public $unknownOptions = [];

    public $rawArgv;

    public $args;

    public $name;

//    protected $commands = [];

    function __construct()
    {

    }

    public function createProperty($key, $value)
    {
        if (!property_exists($this, $key)) {
            $this->$key = $value;
        } else {
            throw new \InvalidArgumentException;
        }
    }

    public function version($v)
    {
        $this->v = $v;
        return $this;
    }

    public function option($flags, $desc)
    {
        array_push($this->options, new Option($flags, $desc));
        return $this;
    }

    public function parse($argv)
    {
        $this->rawArgv = $argv;

        $this->name = basename($argv[0], '.php');

        $this->args = $this->normalize(array_slice($argv, 1));

        $this->parseOptions($this->args);
    }

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

    public function optionFor($arg)
    {
        /**
         * @var Option $option
         */
        foreach ($this->options as $option) {
            if ($option->is($arg)) {
                return $option;
            }
        }

        return false;
    }

    public function parseOptions($args)
    {
        for ($i = 0; $i < count($args); $i++) {
            $option = $this->optionFor($args[$i]);
            if (!$option) {
                if (strlen($args[$i]) > 2 && $args[$i][0] == '-') {
                    array_push($this->unknownOptions, new Option($args[$i]));
                }
            } else {
                $this->createProperty($option->getName(), $args[++$i]);
            }
        }
    }
}
