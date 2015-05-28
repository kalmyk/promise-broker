<?php

use \Kalmyk\Queue\StreamParser;

class StreamParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser = NULL;

    public function setUp()
    {
        $this->parser = new StreamParser();
    }

    public function tearDown()
    {
        $this->assertEquals('', $this->parser->testBuffer(), 'buffer has to be empty at end');
    }

    function testOutboundText()
    {
        $expectedResult = "l2\r\nline 1\r\nline 2\r\n";
        $result = $this->parser->serialize(array('line 1','line 2'), '');

        $this->assertEquals($expectedResult, $result);
    }

    function testOutboundData()
    {
        $expectedResult = "l1d19\r\nline 1\r\ndata\r\ntext\r\nmessage\r\n";
        $result = $this->parser->serialize(array('line 1'), "data\r\ntext\r\nmessage");

        $this->assertEquals($expectedResult, $result);
    }

    function testInboundText()
    {
        $messageText = "l2\r\nline 1 message 12345236\r\nline 2 {:{:{:}:}:}\r\n";
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
                $this->assertEquals(array('line 1 message 12345236','line 2 {:{:{:}:}:}'), $msg);
            }
        }
        $this->assertEquals(100, $found, 'messages lost');
    }

    function testInboundData()
    {
        $text = '>>>line 1 message 12345236 ;lasdfg {:{:{:}:}:} a o;asi fjoai a;lsi fjgo;areij rjas804tu 40tu fdjg;ls<<<';
        $data = "line    1    \r\n \r\nm dsgf \n \n dsfg 34it-9ithg \nline 2 ~!@#$%^^&***((()_+= {:{:{:}:}:}";

        $messageText = "l1d".strlen($data)."\r\n$text\r\n$data\r\n";

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
                $this->assertEquals(2, count($msg));
                $this->assertEquals($text, $msg[0]);
                $this->assertEquals($data, $msg[1]);
            }
        }
        $this->assertEquals(100, $found, 'messages lost');
    }
}
