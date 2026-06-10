<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ApiResponse implements Responsable
{
    /**
     * @param  mixed                $data
     * @param  int                  $status
     * @param  array<string, mixed> $meta
     * @param  array<string, mixed> $links
     * @param  string               $message
     */
    public function __construct(
        protected mixed $data = null,
        protected int $status = 200,
        protected array $meta = [],
        protected array $links = [],
        protected string $message = ''
    ) {}

    /**
     * Cria uma resposta de sucesso.
     */
    public static function success(mixed $data = null, int $status = 200, array $meta = [], array $links = [], string $message = ''): self
    {
        return new self($data, $status, $meta, $links, $message);
    }

    /**
     * Cria uma resposta de erro.
     */
    public static function error(string $message, int $status = 400, mixed $details = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['errors'] = $details;
        }

        return response()->json($payload, $status);
    }

    /**
     * Converte o objeto para uma resposta HTTP JsonResponse.
     */
    public function toResponse($request): JsonResponse
    {
        $payload = [];

        if ($this->message !== '') {
            $payload['message'] = $this->message;
        }

        $payload['data'] = $this->data;

        if (!empty($this->links)) {
            $payload['links'] = $this->links;
        }

        if (!empty($this->meta)) {
            $payload['meta'] = $this->meta;
        }

        return response()->json($payload, $this->status);
    }
}
