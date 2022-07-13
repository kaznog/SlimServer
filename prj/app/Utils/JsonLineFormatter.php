<?php
namespace Utils;

use Monolog\Formatter\FormatterInterface;

/**
 * JSON形式で出力するためのMonolog フォーマッタ.
 */
class JsonLineFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $message = [
            'time' => $record['datetime']->format('Y-m-d H:i:s O'),
            'env' => getenv('APP_ENV'),
            'channel' => $record['channel'],
            'level' => $record['level_name'],
            'host' => gethostname(),
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ];

        return json_encode($message) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }
}

?>