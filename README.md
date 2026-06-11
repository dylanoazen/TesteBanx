# EBANX Banking API

In-memory banking API built for the EBANX take-home assignment.

## Live

```text
https://ebanx.bruggeongrails.com
```

## Requirements

- PHP 8.1+
- [Composer](https://getcomposer.org/)

## Running locally

```bash
composer install
composer start          # starts on port 8080 (override with PORT env var)
```

```bash
PORT=80 composer start
```

## Tests

```bash
composer test
```

## Lint

```bash
composer lint           # check only
composer lint:fix       # apply fixes
```

## Endpoints

| Method | Path                     | Description                            |
| ------ | ------------------------ | -------------------------------------- |
| POST   | `/reset`                 | Clears all accounts. Returns `200 OK`. |
| GET    | `/balance?account_id=ID` | Returns the balance, or `404 0`.       |
| POST   | `/event`                 | `deposit` / `withdraw` / `transfer`.   |

### Examples

```bash
# Reset state
curl -X POST http://localhost:8080/reset

# Check balance (not found)
curl "http://localhost:8080/balance?account_id=1234"
# -> 404 0

# Deposit
curl -X POST http://localhost:8080/event \
  -H "Content-Type: application/json" \
  -d '{"type":"deposit","destination":"100","amount":10}'
# -> 201 {"destination":{"id":"100","balance":10}}

# Withdraw
curl -X POST http://localhost:8080/event \
  -H "Content-Type: application/json" \
  -d '{"type":"withdraw","origin":"100","amount":5}'
# -> 201 {"origin":{"id":"100","balance":5}}

# Transfer
curl -X POST http://localhost:8080/event \
  -H "Content-Type: application/json" \
  -d '{"type":"transfer","origin":"100","destination":"300","amount":5}'
# -> 201 {"origin":{"id":"100","balance":0},"destination":{"id":"300","balance":5}}
```

## Project structure

```text
src/
  Domain/                  business logic — no HTTP knowledge
    Account.php            entity: enforces its own invariants
    AccountRepository.php  in-memory store (array, process-lifetime)
    AccountService.php     deposit / withdraw / transfer / balance / reset
    Exception/             domain errors (AccountNotFound, InsufficientFunds)
  Http/
    EventController.php    translates HTTP <-> service, maps errors to status codes
public/
  server.php               process entry point (ReactPHP wiring)
tests/                     PHPUnit tests against the domain layer
```

## Key decisions

**In-memory state via a long-running process.** PHP's classic request-per-process model discards in-memory state between requests. A long-running [ReactPHP](https://reactphp.org/) server keeps a single `AccountRepository` alive for the whole process lifetime — no database or file needed.

**Atomic transfers.** The debit is validated and applied before the credit, so a failed transfer never leaves the destination credited. On the single-threaded event loop this is effectively atomic with no locking.

**Integer amounts.** The suite works with whole numbers, so balances are modelled as `int` to avoid floating-point issues.

**Domain exceptions.** `AccountNotFoundException` and `InsufficientFundsException` carry the error data. The `EventController` catches them and maps to HTTP status codes (`404`, `422`), keeping the domain layer completely unaware of HTTP.
