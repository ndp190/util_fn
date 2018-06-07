<?php

namespace go1\util_fn;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Messenger\MessageBus;

/**
 * @property string       $callMethod
 * @property string       $callUrl
 * @property string       $callId
 * @property null|Request $request
 */
class Fn
{
    private function __construct()
    {
        $this->callMethod = getenv('FN_METHOD');
        $this->callUrl = getenv('FN_REQUEST_URL');
        $this->callId = getenv('FN_CALL_ID');

        if ($this->callMethod && $this->callUrl) {
            $this->request = Request::create($this->callUrl, $this->callMethod);
            $this->request->headers->set('Authorization', getenv('FN_HEADER_AUTHORIZATION'));
        }
    }

    public static function run(callable $fn)
    {
        $me = new FN;

        stream_set_blocking(STDIN, 0);
        $payload = json_decode(file_get_contents("php://stdin"));

        /** @var MessageBus $bus */
        $bus = $c['message.bus'];

        try {
            $response = call_user_func($fn, $me, $payload);
        }
        catch (UnauthorizedHttpException $e) {
            $response = [
                'error' => [
                    'status'  => $e->getStatusCode(),
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }

        $bus->dispatch(['eventType' => 'com.go1.response', 'data' => $response]);
    }
}
