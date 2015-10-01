<?php

use \Kalmyk\Queue\StreamParser;

class StreamParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser = NULL;

    public function setUp()
    {
    }

    public function tearDown()
    {
        $this->assertEquals('', $this->parser->testBuffer(), 'buffer has to be empty at end');
    }

    function testSerializeDataRaw()
    {
        $this->parser = new StreamParser(false);
        $result = $this->parser->serialize(array('line 1','line 2'), 'data packet');

        $this->assertEquals("l2d11\r\n\"line 1\"\r\n\"line 2\"\r\ndata packet\r\n", $result);
    }

    function testSerializeDataJson()
    {
        $this->parser = new StreamParser(true);
        $result = $this->parser->serialize(array('line 1','line 2'), 'data packet');

        $this->assertEquals("l2d13\r\n\"line 1\"\r\n\"line 2\"\r\n\"data packet\"\r\n", $result);
    }

    function testSerializeDataMultilineRaw()
    {
        $this->parser = new StreamParser(false);

        $result = $this->parser->serialize(array('line 1'), "data\r\ntext\r\nmessage");

        $this->assertEquals(
            "l1d19\r\n\"line 1\"\r\ndata\r\ntext\r\nmessage\r\n",
            $result
        );
    }

    function testSerializeDataMultilineJson()
    {
        $this->parser = new StreamParser(true);

        $result = $this->parser->serialize(array('line 1'), "data\r\ntext\r\nmessage");

        $this->assertEquals(
            "l1d25\r\n\"line 1\"\r\n".'"data\r\ntext\r\nmessage"'."\r\n",
            $result
        );
    }

    function testParseTextRaw()
    {
        $this->parser = new StreamParser(false);

        $messageText = "l2d11\r\n\"line 1 message 12345236\"\r\n\"line 2 {:{:{:}:}:}\"\r\ndata packet\r\n";
        $stream = '';
        for ($i=0; $i < 100; $i++)
            $stream .= $messageText;

        $found = 0;
        while (strlen($stream) > 0)
        {
            $cut = rand(3, 300);
            $messages = $this->parser->parse(substr($stream, 0, $cut));
            $stream = substr($stream, $cut);
            foreach ($messages as $msg)
            {
                $found ++;
                $this->assertEquals(
                    array('line 1 message 12345236','line 2 {:{:{:}:}:}', 'data packet'),
                    $msg);
            }
        }
        $this->assertEquals(100, $found, 'messages lost');
    }

    function testParseTextJson()
    {
        $this->parser = new StreamParser(true);

        $header = 'line 1';
        $data = "line    1    \"\r\n\"34it line 2 ~!@#$%^^&*sa()_-+= {:f{f:g{g:h}h:j}j:k}k";
        $dataJSON = json_encode($data);

        $messageText = "l1d".strlen($dataJSON)."\r\n".json_encode($header)."\r\n$dataJSON\r\n";

        $stream = '';
        for ($i=0; $i < 100; $i++)
            $stream .= $messageText;

        $found = 0;
        while (strlen($stream) > 0)
        {
            $cut = rand(10, 500);
            $messages = $this->parser->parse(substr($stream, 0, $cut));
            $stream = substr($stream, $cut);
            foreach ($messages as $msg)
            {
                $found ++;
                $this->assertEquals(2, count($msg), "message package incorrect");
                $this->assertEquals($header, $msg[0]);
                $this->assertEquals($data, $msg[1]);
            }
        }
        $this->assertEquals(100, $found, 'messages lost');
    }
}
