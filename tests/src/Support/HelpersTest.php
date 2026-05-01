<?php
/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Support;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testArrayFlatten()
    {
        self::assertEquals(['a', 'b', 'c'], array_flatten(['a', ['b', 'key' => ['c']]]));
    }

    public function testArrayMergeAlternate()
    {
        $config = [
            'one',
            'two' => 2,
            'nested' => [],
        ];

        $config = array_merge_alternate($config, [
            'two' => 20,
            'nested' => [
                'first',
            ],
        ]);

        $config = array_merge_alternate($config, [
            'nested' => [
                'second',
            ],
        ]);

        $config = array_merge_alternate($config, [
            'extra',
        ]);

        self::assertEquals([
            'one',
            'two' => 20,
            'nested' => [
                'first',
                'second',
            ],
            'extra',
        ], $config);
    }

    public function testParseHomeDir()
    {
        $this->assertStringStartsWith('/', parse_home_dir('~/path'));
        $this->assertStringStartsWith('/', parse_home_dir('~'));
        $this->assertStringStartsWith('~', parse_home_dir('~path'));
        $this->assertStringEndsWith('~', parse_home_dir('path~'));
    }

    public function testRsyncRsh()
    {
        $this->assertEquals("ssh -p 22", rsync_rsh(['-p', 22]));
        $this->assertEquals("ssh 'argument with spaces'", rsync_rsh(['argument with spaces']));
        $this->assertEquals("ssh 'argument with '' quote'", rsync_rsh(['argument with \' quote']));
    }

    public function testHumanDurationMillis()
    {
        $this->assertEquals('0ms', human_duration(0));
        $this->assertEquals('1ms', human_duration(1));
        $this->assertEquals('999ms', human_duration(999));
    }

    public function testHumanDurationSeconds()
    {
        $this->assertEquals('1s 0ms', human_duration(1000));
        $this->assertEquals('1s 234ms', human_duration(1234));
        $this->assertEquals('59s 999ms', human_duration(59999));
    }

    public function testHumanDurationMinutes()
    {
        $this->assertEquals('1m 0s', human_duration(60000));
        $this->assertEquals('1m 30s', human_duration(90000));
        $this->assertEquals('59m 59s', human_duration(3599999));
    }

    public function testHumanDurationHours()
    {
        $this->assertEquals('1h 0m', human_duration(3600000));
        $this->assertEquals('2h 30m', human_duration(2 * 3600000 + 30 * 60000));
        $this->assertEquals('25h 0m', human_duration(25 * 3600000));
    }

    public function testHumanDurationNegative()
    {
        $this->assertEquals('0ms', human_duration(-100));
    }
}
