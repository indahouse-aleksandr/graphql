<?php

namespace RzCommon\graphql\Log\Formatter;

use Monolog\Formatter\NormalizerFormatter;

class CustomLogstashFormatter extends NormalizerFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $message = [
            'message' => json_decode($record['message'], true),
            'level' => $record['level'],
            'level_name' => $record['level_name'],
            'channel' => $record['channel'],
            'datetime' => $record['datetime']->format('Y-m-d H:i:s'),
        ];
        return $record['datetime']->format('c') . $this->toJson($message) . "\n";
    }
}