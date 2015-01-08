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

    public $_desc;

    public $_action;

    public $_unknownArgs = [];

    /**
     * @var Commander[] array
     */
    public $_subCmds = [];

    /**
     * @var CommandArg[] array
     */
    public $_cmdArgs = [];

    function __construct($name = '', $desc = '')
    {
        $this->_name = $name;

        $this->_desc = $desc;

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
            throw new \Exception(sprintf(
                "'%s' exists as a property in the Commander, please use other property",
                $key
            ));
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
     * @return mixed
     */
    public function parse($argv)
    {
        $this->_rawArgv = $argv;

        $this->_name = $argv[0];

        $this->_args = $this->normalize(array_slice($argv, 1));

        $this->parseOptions($this->_args);

        if (count($this->_args) > 0) {
            $name = $this->_args[0];

            if ($name[0] !== '-') {
                foreach ($this->_subCmds as $cmd) {
                    if ($cmd->_name == $name) {
                        array_shift($this->_unknownArgs);
                        return $this->triggerCmd($cmd);
                    }
                }
            }
        }
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
                array_push($this->_unknownArgs, $args[$i]);
            } else {
                // a little tricky
                if ($option->required) {
                    $nextArg = isset($args[$i + 1]) ? $args[$i + 1] : false;
                } elseif($option->optional){
                    $nextArg = isset($args[$i + 1]) ? $args[$i + 1] : '';
                } else {
                    $nextArg = isset($args[$i + 1]) ? $args[$i + 1] : true;
                }


                if (($option->required && $nextArg[0] === '-')
                    || ($option->required && !$nextArg)
                ) {
                    throw new \Exception(sprintf("error: option `%s' argument missing", $option->getName()));
                }


                if (isset($nextArg[0]) && $nextArg[0] === '-') {
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
    public function triggerOption(Option $option, $value = '')
    {
        if ($option->getName() == 'help') {
            $this->outputHelp();
        } elseif ($option->getName() == 'version') {
            $this->outputVersion();
        } else {
            $this->createProperty($option->getName(), $value);
        }
    }

    /**
     *
     */
    public function outputVersion()
    {
        $version = $this->_name . ' version ' . $this->v . PHP_EOL;

        echo $version;

        exit;

    }

    /**
     * @return string
     * @todo update usage
     */
    public function usage()
    {
        return '[options]';
    }

    /**
     *
     */
    public function outputHelp()
    {
        $help = PHP_EOL;

        $help .= Color::YELLOW . '  Usage: ' . $this->_name . ' ' . $this->usage() . PHP_EOL;

        $help .= PHP_EOL;

        if (count($this->_subCmds) > 0) {
            $help .= Color::WHITE . '  Commands:' . PHP_EOL;
            $help .= PHP_EOL;
            $help .= implode($this->getCommandHelp(), PHP_EOL) . PHP_EOL;
            $help .= PHP_EOL;
        }

        $help .= '  Options:' . PHP_EOL;

        $help .= PHP_EOL;

        $help .= implode($this->getOptionHelp(), PHP_EOL) . PHP_EOL;

        $help .= PHP_EOL;

        echo $help;

        exit;
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
            array_push(
                $ret,
                Color::GREEN . '    ' . $this->pad($option->rawFlags, $width) . '  ' . Color::WHITE . $option->desc
            );
        }

        return $ret;
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

    /**
     * @param $name
     * @param string $desc
     * @return Commander
     */
    public function command($name, $desc = '')
    {
        $cmdArgs = preg_split("/[\s,]+/", $name);

        $cmd = new self(array_shift($cmdArgs), $desc);

        $this->parseExpectedArgs($cmdArgs, $cmd);

        array_push($this->_subCmds, $cmd);

        return $cmd;
    }

    /**
     * @param $cmdArgs
     * @param $cmd
     * @throws \Exception
     */
    public function parseExpectedArgs($cmdArgs, $cmd)
    {
        if (count($cmdArgs) === 0) {
            return;
        }

        foreach ($cmdArgs as $arg) {
            $argDetail = new CommandArg();
            switch ($arg[0]) {
                case '<':
                    $argDetail->required = true;
                    $argDetail->name = substr($arg, 1, strlen($arg) - 2);
                    break;
                case '[':
                    $argDetail->name = substr($arg, 1, strlen($arg) - 2);
                    break;
                default:
                    ;
            }

            if (strlen($argDetail->name) > 3 && substr($argDetail->name, -3, 3) === '...') {
                $argDetail->variadic = true;
                $argDetail->name = substr($argDetail->name, 0, strlen($argDetail->name) - 3);
            }

            if (!empty($argDetail->name)) {
                array_push($cmd->_cmdArgs, $argDetail);
            }
        }

        // the variadic must be the last

        foreach ($cmd->_cmdArgs as $index => $cmdArg) {
            if ($cmdArg->variadic && $index != count($cmd->_cmdArgs) - 1) {
                throw new \Exception(sprintf("error: variadic arguments must be last `%s'", $cmdArg->name));
            }
        }
    }

    /**
     * @param Commander $cmd
     * @return mixed
     * @throws \Exception
     */
    public function triggerCmd($cmd)
    {
        foreach ($cmd->_cmdArgs as $index => $cmdArg) {

            if ($cmdArg->required) {
                if (!isset($this->_unknownArgs[$index])) {
                    throw new \Exception(sprintf("error: missing required argument `%s'", $cmdArg->name));
                }
            }

            if (!$cmdArg->required && !$cmdArg->variadic) {
                if (!isset($this->_unknownArgs[$index])) {
                    array_push($this->_unknownArgs, '');
                }
            }

            if ($cmdArg->variadic) {
                if (!isset($this->_unknownArgs[$index])) {
                    array_push($this->_unknownArgs, []);
                } else {
                    $variadicArg = $this->_unknownArgs;

                    array_splice($this->_unknownArgs, $index);

                    array_splice($variadicArg, 0, $index);

                    array_push($this->_unknownArgs, $variadicArg);

                }
            }
        }

        return call_user_func_array($cmd->_action, $this->_unknownArgs);
    }

    /**
     * @param $callback
     */
    public function action($callback)
    {
        $this->_action = $callback;
    }

    /**
     * @param CommandArg $cmdArg
     * @return string
     */
    public function humanReadableArgName(CommandArg $cmdArg)
    {
        $nameOutput = $cmdArg->name . ($cmdArg->variadic ? '...' : '');

        return $cmdArg->required ?
            '<' . $nameOutput . '>' :
            '[' . $nameOutput . ']';
    }

    /**
     * @return array
     */
    public function getCommandHelp()
    {

        $ret = [];

        $width = $this->getLargestCmdWidth();

        foreach ($this->_subCmds as $cmd) {
            $cmdHelpLine = $cmd->_name;
            foreach ($cmd->_cmdArgs as $cmdArg) {
                $cmdHelpLine .= ' ' . $this->humanReadableArgName($cmdArg);
            }
            array_push(
                $ret,
                Color::GREEN . '    ' . $this->pad($cmdHelpLine, $width) . '  ' . Color::WHITE . $cmd->_desc
            );
        }

        return $ret;
    }

    /**
     * @return int|mixed
     */
    public function getLargestCmdWidth()
    {
        $width = 0;
        foreach ($this->_subCmds as $cmd) {
            $cmdHelpLine = $cmd->_name;
            foreach ($cmd->_cmdArgs as $cmdArg) {
                $cmdHelpLine .= '  ' . $this->humanReadableArgName($cmdArg);
            }
            $width = max($width, strlen($cmdHelpLine));
        }

        return $width;
    }

}
