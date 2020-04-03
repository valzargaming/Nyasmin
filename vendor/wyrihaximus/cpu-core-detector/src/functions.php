<?php

namespace WyriHaximus\CpuCoreDetector;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Server;
use WyriHaximus\CpuCoreDetector\Core\Affinity\CmdExe;
use WyriHaximus\CpuCoreDetector\Core\Affinity\Taskset;
use WyriHaximus\CpuCoreDetector\Core\AffinityCollection;
use WyriHaximus\CpuCoreDetector\Core\Count\Nproc;
use WyriHaximus\CpuCoreDetector\Core\Count\WindowsEcho;
use WyriHaximus\CpuCoreDetector\Core\CountCollection;
use WyriHaximus\CpuCoreDetector\Detector\Hash;
use WyriHaximus\CpuCoreDetector\Detector\Where;
use WyriHaximus\React\ProcessOutcome;

/**
 * @return Collections
 */
function getDefaultCollections(LoopInterface $loop)
{
    return new Collections(
        getDefaultDetectors($loop),
        getDefaultCounters($loop),
        getDefaultAffinities($loop)
    );
}

/**
 * @return DetectorCollection
 */
function getDefaultDetectors(LoopInterface $loop)
{
    return new DetectorCollection([
        new Hash($loop),
        new Where($loop),
    ]);
}

/**
 * @return CountCollection
 */
function getDefaultCounters(LoopInterface $loop)
{
    return new CountCollection([
        new Nproc($loop),
        new WindowsEcho($loop),
    ]);
}

/**
 * @return AffinityCollection
 */
function getDefaultAffinities(LoopInterface $loop)
{
    return new AffinityCollection([
        new Taskset(),
        new CmdExe(),
    ]);
}

function launch($command, LoopInterface $loop)
{
    $buffers = [
        'stderr' => '',
        'stdout' => '',
    ];

    $server = new Server('127.0.0.1:0', $loop);
    $server->on('connection', function (ConnectionInterface $connection) use (&$buffers) {
        $connection->on('data', function ($chunk) use (&$buffers) {
            $buffers['stdout'] .= $chunk;
        });
    });

    $code = '$s=stream_socket_client($argv[1]);do{fwrite($s,$d=fread(STDIN, 8192));}while(isset($d[0]));';
    $command = $command . ' | php  -r ' . escapeshellarg($code) . ' ' . escapeshellarg($server->getAddress());
    if (\DIRECTORY_SEPARATOR === '\\') {
        $command = 'cmd /c ' . $command;
    }

    $process = new Process($command, null, null, StaticConfig::getFileDescriptorList());
    return \WyriHaximus\React\childProcessPromise(
        $loop,
        $process
    )->then(function (ProcessOutcome $outcome) use (&$buffers) {
        return new ProcessOutcome($outcome->getExitCode(), $buffers['stderr'], $buffers['stdout']);
    });
}
