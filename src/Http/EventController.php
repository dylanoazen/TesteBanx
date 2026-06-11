<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\AccountService;
use App\Domain\Exception\AccountNotFoundException;
use App\Domain\Exception\InsufficientFundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

final class EventController
{
    private readonly AccountService $service;

    public function __construct(AccountService $service)
    {
        $this->service = $service;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        return match (true) {
            $method === 'POST' && $path === '/reset'   => $this->reset(),
            $method === 'GET'  && $path === '/balance' => $this->balance($request),
            $method === 'POST' && $path === '/event'   => $this->event($request),
            default                                     => $this->plain(404, '0'),
        };
    }

    private function event(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        if ($data === null) {
            return $this->plain(400, '0');
        }

        return match ($data['type'] ?? null) {
            'withdraw' => $this->withdraw($data),
            'deposit'  => $this->deposit($data),
            'transfer' => $this->transfer($data),
            default     => $this->plain(400, '0'),
        };
    }

    private function plain(int $status, string $body): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'text/plain'], $body);
    }

    private function json(int $status, array $body): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }


    private function reset(): ResponseInterface
    {
        $this->service->reset();

        return $this->plain(200, 'OK');
    }


    // Functions (Operations)
    private function balance(ServerRequestInterface $request): ResponseInterface
    {
        $accountId = $request->getQueryParams()['account_id'] ?? null;
        if ($accountId === null) {
            return $this->plain(400, '0');
        }

        try {
            $balance = $this->service->balance($accountId);
            return $this->plain(200, (string)$balance);
        } catch (AccountNotFoundException) {
            return $this->plain(404, '0');
        }
    }

    private function withdraw(array $data): ResponseInterface
    {
        if (!isset($data['origin']) || $data['amount'] === null) {
            return $this->plain(400, '0');
        }

        try {
            $account = $this->service->withdraw($data['origin'], (int)$data['amount']);
            return $this->json(201, ['origin' => $account->toArray()]);
        } catch (AccountNotFoundException) {
            return $this->plain(404, '0');
        } catch (InsufficientFundsException) {
            return $this->plain(422, '0');
        }
    }

    private function deposit(array $data): ResponseInterface
    {
        if (!isset($data['destination']) || $data['amount'] === null) {
            return $this->plain(400, '0');
        }
        $account = $this->service->deposit($data['destination'], (int)$data['amount']);
        return $this->json(201, ['destination' => $account->toArray()]);
    }

    private function transfer(array $data): ResponseInterface
    {
        if (!isset($data['origin']) || !isset($data['destination']) || $data['amount'] === null) {
            return $this->plain(400, '0');
        }

        try {
            $result = $this->service->transfer($data['origin'], $data['destination'], (int)$data['amount']);
            return $this->json(201, [
                'origin' => $result['origin']->toArray(),
                'destination' => $result['destination']->toArray(),
            ]);
        } catch (AccountNotFoundException) {
            return $this->plain(404, '0');
        } catch (InsufficientFundsException) {
            return $this->plain(422, '0');
        }
    }
}
