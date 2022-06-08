<?php

final class EscalatieConverter
{
    public static function process(string $path)
    {
        $lines = @file_get_contents($path);

        if ($lines === false) {
            throw new \RuntimeException(sprintf('Could not read input file "%s"!', $path));
        }

        $lines = explode(PHP_EOL, $lines);
        $lines = array_map('trim', $lines);

        $events = [];

        foreach ($lines as $line) {
            if (!preg_match('/^(\d+:\d{2}:\d{2}.\d{3}) (.*?)$/', $line, $matches)) {
                echo 'Warning: ignoring the next line because it does not start with a timestamp:'.PHP_EOL;
                echo $line.PHP_EOL;
                break;
            }

            $ts = $matches[1];
            $line = $matches[2];

            if (!is_null($song = self::trySong($ts, $line))) {
                $events[] = $song;
            } else if (!is_null($horn = self::tryHorn($ts, $line))) {
                $events[] = $horn;
            } else {
                echo 'Warning: ignoring the next line because it could not be parsed:'.PHP_EOL;
                echo $line.PHP_EOL;
            }
        }

        echo json_encode($events, JSON_PRETTY_PRINT);
    }

    private static function trySong(string $ts, string $line): ?object
    {
        if (!preg_match('/^(\\+?)(.*?) - (.*?)$/', $line, $matches)) {
            return null;
        }

        return (object)[
            'ts' => $ts,
            'type' => 'song',
            'artist' => trim($matches[2]),
            'title' => trim($matches[3]),
            'mashup' => $matches[1] ? true : false
        ];
    }

    private static function tryHorn(string $ts, string $line): ?object
    {
        if (!preg_match('/^horn$/', $line)) {
            return null;
        }

        return (object)[
            'ts' => $ts,
            'type' => 'horn'
        ];
    }
}

if ($argc > 1) {
    try {
        EscalatieConverter::process($argv[1]);
    } catch (\Exception $e) {
        echo 'Exception: '.$e->getMessage().PHP_EOL;
        exit(1);
    }
} else {
    echo 'Usage: php '.$argv[0].' FILE';
}