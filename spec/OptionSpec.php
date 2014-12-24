<?php

namespace spec\Lijinma;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->beConstructedWith("flags", "desc");

        $this->shouldHaveType('Lijinma\Option');
    }

    function it_can_add_flags_and_an_desc()
    {
        $this->beConstructedWith('-C, --chdir <path>', 'change the working directory');

        $this->flags->shouldBe(['-C', '--chdir', '<path>']);

        $this->desc->shouldBe('change the working directory');
    }

    function it_can_add_long_and_short()
    {
        $this->beConstructedWith('-C, --chdir', 'change the working directory');

        $this->short->shouldBe('-C');

        $this->long->shouldBe('--chdir');
    }

    function it_can_be_optional()
    {
        $this->beConstructedWith('[dir]', 'the working directory');

        $this->optional->shouldBe(true);
    }

    function it_can_be_required()
    {
        $this->beConstructedWith('<dir>', 'the working directory');

        $this->required->shouldBe(true);
    }

    function it_can_get_the_option_name()
    {
        $this->beConstructedWith('-C, --chdir', 'change the working directory');

        $this->getName()->shouldBe('chdir');
    }

    function it_check_the_arg_match_the_short_or_long()
    {
        $this->beConstructedWith('-C, --chdir', 'change the working directory');

        $this->is('-C')->shouldReturn(true);

        $this->is('--chdir')->shouldReturn(true);

        $this->is('--ch')->shouldReturn(false);
    }

}
