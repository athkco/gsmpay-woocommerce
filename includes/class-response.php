<?php
if (!defined('ABSPATH')) {
    exit;
}

class GSMPay_Http_Response
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    private $decodedBody = [];

    /**
     * Response constructor.
     *
     * @param int $statusCode
     * @param string $body
     */
    public function __construct($statusCode, $body)
    {
        $this->statusCode = (int)$statusCode;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (! $this->decodedBody) {
            $this->decodedBody = (array) json_decode($this->body, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
        }

        return $this->decodedBody;
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getErrorType()
    {
        return !$this->isSuccessful() ? $this->toArray()['type'] : null;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        if ($this->isSuccessful()) {
            return null;
        }

        if ($this->isJson($this->body())) {
            $response = $this->toArray();

            if ($this->getErrorType() === 'validation_error') {
                $messages = $this->flattenValidationErrors($response['errors']);

                return implode(' - ', $messages);
            }

            if (!empty($response['message'])) {
                return $response['message'];
            }
        }

        return __('خطای ناشناخته در اتصال به درگاه پرداخت.', WC_GSMPAY_TRANSLATE_DOMAIN);
    }

    private function flattenValidationErrors(array $errors)
    {
        $flattened = [];

        foreach ($errors as $messages) {
            foreach ($messages as $message) {
                $flattened[] = $message;
            }
        }

        return $flattened;
    }

    private function isJson($value)
    {
        if (! is_string($value)) {
            return false;
        }

        if (function_exists('json_validate')) {
            return json_validate($value, 512);
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return false;
        }

        return true;
    }
}
