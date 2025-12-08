<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSJT-X Automation Proxy</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header>
    <h1>WSJT-X Automation Proxy</h1>
    <p>Control PTT, send messages, and log QSOs from the browser.</p>
</header>

<main>
    <section>
        <h2>Status</h2>
        <button id="refreshStatus">Refresh Status</button>
        <pre id="statusBox">Waiting for status...</pre>
    </section>

    <section>
        <h2>PTT</h2>
        <div class="ptt-controls">
            <button id="pttOn">Engage PTT</button>
            <button id="pttOff">Release PTT</button>
        </div>
    </section>

    <section>
        <h2>Transmit & Log QSO</h2>
        <form id="transmitForm">
            <label>
                Message
                <input type="text" name="message" required placeholder="CQ TEST DE CALLSIGN">
            </label>
            <div class="two-col">
                <label>Callsign <input name="callSign" required></label>
                <label>Grid <input name="grid" placeholder="FN31"></label>
            </div>
            <div class="two-col">
                <label>Mode <input name="mode" value="FT8"></label>
                <label>RST Sent <input name="rstSent" value="+00"></label>
                <label>RST Recv <input name="rstRecv" value="+00"></label>
            </div>
            <label>Notes <input name="notes" placeholder="Optional notes"></label>
            <button type="submit">Transmit & Log</button>
        </form>
    </section>

    <section>
        <h2>Log</h2>
        <button id="refreshLogs">Refresh Log</button>
        <div id="logList"></div>
    </section>
</main>

<script src="assets/app.js"></script>
</body>
</html>

