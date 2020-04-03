<?php

namespace WyriHaximus\CpuCoreDetector;

use WyriHaximus\FileDescriptors\Factory as FileDescriptorsFactory;
use WyriHaximus\FileDescriptors\ListerInterface;
use WyriHaximus\FileDescriptors\NoCompatibleListerException;

final class StaticConfig
{
    public static function shouldListFileDescriptors()
    {
        static $should = null;
        if ($should !== null) {
            return $should;
        }

        $arguments = (new \ReflectionClass('React\ChildProcess\Process'))->getConstructor()->getParameters();
        if (!isset($arguments[3])) {
            return $should = false;
        }

        return $should = ($arguments[3]->getName() === 'fds');
    }

    public static function getFileDescriptorList()
    {
        if (\DIRECTORY_SEPARATOR === '\\') {
            return [];
        }

        if (!self::shouldListFileDescriptors()) {
            return [];
        }

        static $fileDescriptorLister = null;

        if ($fileDescriptorLister === false) {
            return [];
        }

        if ($fileDescriptorLister === null) {
            try {
                $fileDescriptorLister = FileDescriptorsFactory::create();
            } catch (NoCompatibleListerException $exception) {
                $fileDescriptorLister = false;

                return [];
            }
        }


        $fds = [];
        foreach (self::listFileDescriptors($fileDescriptorLister) as $id) {
            $fds[(int)$id] = ['file', '/dev/null', 'r'];
        }
        return $fds;
    }

    private static function listFileDescriptors(ListerInterface $fileDescriptorLister)
    {
        if (\method_exists($fileDescriptorLister, 'list')) {
            return $fileDescriptorLister->list();
        }

        return $fileDescriptorLister->listFileDescriptors();
    }
}
