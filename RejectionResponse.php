<?php

namespace AachenerRegistrationGuard\Services;

use Plenty\Plugin\Http\Response;

class RejectionResponse
{
    public function payload(string $message): array
    {
        // Both LTS and the observed B2B form use NotificationService.error(response.error).
        // Code 0 preserves our message instead of selecting an unrelated Ceres translation.
        return [
            'error' => [
                'code' => 0,
                'message' => $message,
                'stackTrace' => [],
                'placeholder' => null
            ],
            'message' => $message,
            'errors' => ['email' => [$message]],
            'data' => null,
            'events' => []
        ];
    }

    /**
     * A Plenty before() middleware has no documented short-circuit return contract.
     * Explicitly finish the response, as IO's AbstractGuard does for redirects.
     * Returning a Response here could let the registration controller run anyway.
     */
    public function send(string $message, int $status): void
    {
        /** @var Response $response */
        $response = pluginApp(Response::class);
        $response = $response->make(
            json_encode($this->payload($message), JSON_UNESCAPED_UNICODE),
            $status,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff'
            ]
        );
        $response->forceStatus($status);
        $response->sendHeaders();
        echo $response->content();
        exit;
    }
}
