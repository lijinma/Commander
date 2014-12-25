<?php

namespace spec\Lijinma;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CommanderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Lijinma\Commander');
    }

    function it_has_the_help_option()
    {
        $this->_options->shouldHaveCount(1);
    }

    function it_can_be_set_the_version()
    {
        $this->version('1.0.0')->shouldReturn($this);

        $this->_options->shouldHaveCount(2);
    }

    function it_can_add_multiple_options()
    {
        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $this->_options->shouldHaveCount(3);
    }

    function it_can_nomalize_args()
    {
        $this->normalize(['-a'])->shouldReturn(['-a']);

        $this->normalize(['--test'])->shouldReturn(['--test']);

        $this->normalize(['-abc'])->shouldReturn(['-a', '-b', '-c']);

        $this->normalize(['--name=jinma'])->shouldReturn(['--name', 'jinma']);

        $this->normalize(['-n', 'lijinma', '--sex', 'male'])->shouldReturn(['-n', 'lijinma', '--sex', 'male']);
    }

    function it_can_check_whether_the_arg_exist_in_options()
    {

        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $this->optionFor('-p')->shouldReturnAnInstanceOf('Lijinma\Option');
        $this->optionFor('--bbq')->shouldReturnAnInstanceOf('Lijinma\Option');
        $this->optionFor('--unkownOption')->shouldReturn(false);

    }

    function it_can_add_property()
    {
        $this->createProperty('key', 'value');

        $this->key->shouldBe('value');
    }

    function it_throws_exception_when_add_a_existed_property()
    {
        $this->shouldThrow('\Exception')->duringCreateProperty('_name', 'value');
    }


    function it_can_parse_options()
    {
        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $args = ['-p', 'pepper1'];

        $this->parseOptions($args);

        $this->peppers->shouldBe('pepper1');
    }

    function it_can_add_unknownArgs()
    {
        $args = ['unknown1', 'unknown2'];

        $this->parseOptions($args);

        $this->_unknownArgs->shouldHaveCount(2);
    }

    function it_will_throw_exception_if_gets_a_unknown_option()
    {
        $this->option('-p, --peppers <pepper-name>', 'Add peppers');

        $args = ['-a'];

        $this->shouldThrow('\Exception')->duringParseOptions($args);

        $args = ['--aaaa'];

        $this->shouldThrow('\Exception')->duringParseOptions($args);

    }

    function it_will_throw_exception_if_required_option_is_not_set()
    {
        $this->option('-p, --peppers <pepper-name>', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $args = ['-p', '-b'];

        $this->shouldThrow('\Exception')->duringParseOptions($args);

        $args = ['-p'];

        $this->shouldThrow('\Exception')->duringParseOptions($args);

    }


    function it_can_add_name_and_args_when_parse_argv()
    {
        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $argv = ['test.php', '-p', 'pepper1'];

        $this->parse($argv);

        $this->_args->shouldBe(['-p', 'pepper1']);

        $this->_name->shouldBe('test.php');
    }

    function it_can_parse_argv_and_create_property()
    {
        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $argv = ['test.php', '-p', 'pepper1'];

        $this->parse($argv);

        $this->peppers->shouldBe('pepper1');
    }

    //help

    function it_can_get_the_largest_option_width()
    {
        $this->option('-p, --peppers', 'Add peppers')
            ->option('-b, --bbq', 'Add bbq sauce');

        $this->getLargestOptionWidth()->shouldReturn(13);
    }


    function it_can_pad_string_to_width()
    {
        $this->pad('-p, --peppers', 20)->shouldReturn('-p, --peppers       ');
    }


    function it_can_get_the_usage()
    {
        $this->usage()->shouldReturn('[options]');
    }


    //add commands

    function it_can_add_command()
    {
        $this->command('rmdir <dir> [otherDirs...]', 'Remove the directory')
            ->shouldHaveType('Lijinma\Commander');

        $this->_cmds->shouldHaveCount(1);

        $this->_cmds[0]->_name->shouldBe('rmdir');
    }

    function it_will_throw_exception_if_variadic_arguments_is_not_last(){

        $this->shouldThrow('\Exception')->duringCommand('rmdir [otherDirs...] [dir]', 'Remove the directory');

    }

    function it_can_parse_expected_args()
    {
        $args = ['<dir>', '[otherDirs...]'];

        $this->parseExpectedArgs($args);

        $this->_cmdArgs->shouldHaveCount(2);
    }

    function it_can_add_action_to_command()
    {
        $this->command('rmdir <dir> [otherDirs...]', 'Remove the directory')
            ->action(function(){});
    }

    function it_will_throw_exception_if_missing_a_required_arg_for_a_command()
    {
        $this->command('rmdir <dir> [otherDirs...]', 'Remove the directory')
            ->action(function(){});

        $argv = ['test.php', 'rmdir'];

        $this->shouldThrow('\Exception')->duringParse($argv);

    }

    function it_will_execute_the_action_as_expected()
    {
        $this->command('rmdir <dir>', 'Remove the directory')
            ->action(function($dir){
                    return $dir;
                });

        $argv = ['test.php', 'rmdir', 'testDir'];

        $this->parse($argv)->shouldReturn('testDir');
    }

}
