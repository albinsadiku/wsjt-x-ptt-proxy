## WSJT-X Automation Proxy

PHP 8.2 web app that exposes a tiny HTTP API + web UI to drive WSJT-X PTT and messages, and to log QSOs (file-based by default).

### Quick start
- Copy `env.sample` to `.env` and set `WSJTX_HOST` / `WSJTX_PORT` (defaults: `127.0.0.1:2237`).
- Serve `public/` (e.g., `php -S 0.0.0.0:8000 -t public`).
- Open http://localhost:8000 to control PTT, transmit, and view logs.

### Ports and communication
- HTTP UI/API: your chosen web server port (example above is `8000`). Requests are plain HTTP: GET for reads, POST for PTT/transmit.
- WSJT-X link: `WsjtXClient` opens a TCP socket (`fsockopen`) to `WSJTX_HOST:WSJTX_PORT` and writes JSON payloads. Replace `mockSend` with CAT/PTT or the official WSJT-X UDP protocol as needed.
- Logging: writes to `storage/logs/qso-log.json` via file I/O only (no extra ports).

### API surface
- `GET /api/status` — rig status + PTT state + log count.
- `POST /api/ptt` — body `engage=true|false`.
- `POST /api/transmit` — body `message` plus optional `callSign`, `grid`, `mode`, `rstSent`, `rstRecv`, `notes` to log the QSO.
- `GET /api/logs` — returns all log entries.

### Why a proxy?
- Keeps WSJT-X off the public surface; you can add auth/rate limits/queues here.
- Simple auditing via the log repository; swap to a database if needed.

